<?php


namespace Academe\Proj;

/**
 * Class Proj4Importer
 * @package Academe\Proj
 * This class extracts data from Proj4.
 */
class Proj4Importer
{
    protected $targetDirectory = __DIR__ . '/data';

    /**
     * @param $params Params to be passed to proj. These are not escaped!
     * @return string[]
     */
    protected function getList($params = '')
    {
        return array_filter(explode("\n", shell_exec('proj -l' . $params)));
    }

    /**
     * @param array $data The data to be saved.
     * @param string $fileName The name of the file.
     * @return bool whether the json file was created.
     */
    protected function saveAsJson(array $data, $fileName)
    {
        return false !== file_put_contents($this->targetDirectory . '/' . $fileName . '.json', json_encode($data, JSON_PRETTY_PRINT));
    }


    public function importProjections()
    {
        $data = [];
        // Parse the list.
        foreach($this->getList() as $line) {
            list($id, $name) = explode(':', $line);
            $data[trim($id)] = [
                'name' => trim($name),
                'source' => $line
            ];
        }
        $this->saveAsJson($data, 'projections');

    }

    public function importEllipsoids()
    {
        $data = [];
        // Parse the list.
        foreach($this->getList('e') as $line) {
            if (preg_match('/^\s*(?<id>.*?)\s*a=(?<a>\d+\.\d*)\.?\s*(?:(?:rf=(?<rf>\d+\.\d*))|(?:b=(?<b>\d+\.\d*)))\s*(?<name>.*?)\s*$/', $line, $matches)) {
                $entry = [];
                foreach($matches as $key => $val) {
                    if (is_string($key) && $key !== 'id') {
                        $entry[$key] = Proj4Config::convert($val, $key);
                    }
                }
                $entry['source'] = $line;
                $data[$matches['id']] = array_filter($entry);
            } else {
                throw new \Exception("Could not parse line: $line");
            }
        }
        $this->saveAsJson($data, 'ellipsoids');
    }

    public function importUnits()
    {
        $data = [];
        // Parse the list.
        foreach($this->getList('u') as $line) {
            if (preg_match('/^\s*(?<id>.+?)\s+(?<val>.+?)\s+(?<name>.+?)\s*$/', $line, $matches)) {
                $data[$matches['id']] = [
                    'name' => $matches['name'],
                    'size' => Proj4Config::convert($matches['val'], $matches['id']),
                    'source' => $line
                ];
            } else {
                throw new \Exception("Could not parse line: $line");
            }
        }
        $this->saveAsJson($data, 'units');
    }

    public function importDatums()
    {
        $data = [];

        $lines = $this->getList('d');
        array_shift($lines);
        for($i = 1; $i < count($lines); $i++) {
            if (strncmp('                    ', $lines[$i], 20) === 0) {
                $lines[$i - 1] .= $lines[$i];
                $lines[$i] = null;
            }
        }
        foreach(array_filter($lines) as $line) {
            if (preg_match('/^\s*(?<id>.+?)\s+(?<ellipse>.+?)\s+(?<def>.+?)(?:\s+(?<comment>.*?))?\s*$/', $line, $matches)) {
                $data[$matches['id']] = [
                    'ellipse' => $matches['ellipse'],
                    'def' => Proj4Config::convert($matches['def'], 'def'),
                    'comment' => $matches['comment'],
                    'source' => $line
                ];
            } else {
                throw new \Exception("Could not parse line: $line");
            }

        }
        $this->saveAsJson($data, 'datums');
    }

}
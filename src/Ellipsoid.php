<?php namespace Academe\Proj;

/**
 * Defines an ellisoid.
 * How do the named ellipsoids link in, and how do we register additional ellipsoids?
 * Maybe managing the ellipsoid data sources is not the job of this class.
 */

/*
 * semi-major axis             (a)
 * semi-minor axis             (b)
 * flattening                  (f)   = (a-b)/a
 * flattening inverse          (f-1) = (1/f)
 * first eccentricity          (e)   = sqrt(1-(b2/a2))
 * first eccentricity squared  (e2)  = (a2-b2)/a2
 * second eccentricity         (e`)  = sqrt((a2/b2)-1)
 * second eccentricity squared (e`2) = (a2-b2)/b2
 */

use Exception;

class Ellipsoid
{
    /**
     * The English name of the ellipsoid.
     * Example: International 1909 (Hayford)
     */
    protected $name;

    /**
     * The sort name of the ellipsoid.
     * Examples: evrst56, WGS66
     */
    protected $code;

    /**
     * Source parameters as supplied.
     * Values not supplied are derived when needed.
     */

    // Equitorial radius, metres.
    // Semi-major axis.
    protected $a;

    // Polar radius, metres.
    // Semi-minor axis.
    protected $b;

    // Flattening.
    protected $f;

    // Reciprocal of flattening.
    protected $rf;

    /**
     * Initialise.
     * Parameters are a+b or a+rf
     * a = equitorial radius
     * b = polar radius
     * rf = reciprocal of flattening = 1/((a-b)/a) or a/(a-b)
     * Both b and rf can be derived from the other.
     *
     * TODO: have a think about ways this could be initialised:
     *  a, b (string or params)
     *  a, rf (string or params)
     *  withA(a), withB(b), withAB(a, b), withF(f), withRF(rf), withARF(a, rf)
     *  byName(name)
     * etc.
     * 'a' is always required; b/f/rf go withy it.
     * Proj.4 parameters are: +a=<metres> +b=<metres> +ellps=<name> +f=<value> +rf=<value>
     * The aim isn't to parse Proj.4 strings here, but to provide a close match.
     */
    public function __construct(array $params = null)
    {
        // If no parameters provided, then default to WGS84 ellipsoid.
        if (empty($params)) {
            $params = [
                'a' => 6378137.0,
                'rf' => 298.257223563,
                'code' => 'WGS84',
                'name' => 'WGS 84'
            ];
        }

        // Go through the parameters and take set up each in turn.
        foreach($params as $key => $value) {
            switch (strtolower($key)) {
                case 'a':
                    $this->a = (float)$value;
                    break;
                case 'b':
                    $this->setB($value);
                    break;
                case 'f':
                    $this->setF($value);
                    break;
                case 'rf':
                    $this->setRF($value);
                    break;
                case 'code':
                    $this->code = $value;
                    break;
                case 'name':
                    $this->name = $value;
                    break;
                case 'ellps':
                    // TODO: get the parameters from the name.
                    // The value of ellps will be the code.
                    break;
            }
        }

        // Raise an exception if at least 'a' and one of 'b', 'f' or 'rf' are not set.
        if ( ! isset($this->a)) {
            throw new Exception('');
        }
    }

    /**
     * Get short name.
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add a short name.
     */
    public function withCode($code)
    {
        $clone = clone $this;
        $clone->code = $code;
        return $clone;
    }

    /**
     * Get long name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a long name.
     */
    public function withName($name)
    {
        $clone = clone $this;
        $clone->name = $name;
        return $clone;
    }

    /**
     * Get 'a', which will always be set.
     */
    public function getA()
    {
        return $this->a;
    }

    /**
     * Get 'b', deriving it if necessary.
     */
    public function getB()
    {
        if (isset($this->b)) {
            return $this->b;
        }

        if (isset($this->f)) {
            // f = (a-b)/a ∴ fa = a-b ∴ b = a-fa
            return $this->a - ($this->f * $this->a);
        }

        if (isset($this->rf)) {
            // rf = a/(a-b) ∴ rf/a = a-b ∴ b = a-rf/a
            return $this->a - ($this->rf / $this->a);
        }
    }

    /**
     * Set 'b'.
     */
    protected function setB($value)
    {
        $this->b = (float)$value;
        $this->f = null;
        $this->rf = null;
    }

    /**
     * Return clone with 'b' set.
     */
    public function withB($value)
    {
        $clone = clone $this;
        $clone->setB($value);
        return $clone;
    }

    /**
     * Get 'f', deriving it if necessary.
     */
    public function getF()
    {
        if (isset($this->f)) {
            return $this->f;
        }

        if (isset($this->b)) {
            // f = (a-b)/a
            return ($this->a - $this->b) / $this->a;
        }

        if (isset($this->rf)) {
            // r = 1/rf
            return 1 / $this->rf;
        }
    }

    /**
     * Set 'f'.
     */
    protected function setF($value)
    {
        $this->b = null;
        $this->f = (float)$value;
        $this->rf = null;
    }

    /**
     * Return clone with 'f' set.
     */
    public function withF($value)
    {
        $clone = clone $this;
        $clone->setF($value);
        return $clone;
    }

    /**
     * Get 'rf', deriving it if necessary.
     * Note that rf for a sphere will be infinity (divide by zero).
     */
    public function getRF()
    {
        if (isset($this->rf)) {
            return $this->rf;
        }

        if (isset($this->b)) {
            // rf = a/(a-b)
            return $this->a / ($this->a - $this->b);
        }

        if (isset($this->f)) {
            // rf = 1/f
            return 1 / $this->f;
        }
    }

    /**
     * Set 'rf'.
     */
    protected function setRF($value)
    {
        $this->b = null;
        $this->f = null;
        $this->rf = (float)$value;
    }

    /**
     * Return clone with 'rf' set.
     */
    public function withRF($value)
    {
        $clone = clone $this;
        $clone->setRF($value);
        return $clone;
    }

    /**
     * Return the first eccentricity squared.
     */
    public function getES()
    {
        // Calculate from a and b, if b has been supplied.
        if (isset($this->b)) {
            $a2 = $this->a * $this->a;
            $b = $this->b;
            $b2 =  $this->b * $this->b;

            return ($a2 - $b2) / $a2;
        } else {
            // Otherwise use f.
            $f = $this->getF();

            return (2 * $f) - ($f * $f);
        }
    }

    /**
     * Return the first eccentricity.
     */
    public function getE()
    {
        $es = $this->getES();

        return sqrt($es);
    }

    /**
     * Magic getter access to the parameters.
     * TODO: return somethign sensible for 'ellps'.
     */
    public function __get($name)
    {
        switch (strtolower($name)) {
            case 'a':
                return $this->getA();
            case 'b':
                return $this->getB();
            case 'f':
                // Flattening.
                return $this->getF();
            case 'rf':
                // Reciprocal flattening.
                return $this->getRF();
            case 'e':
                // First eccentricity.
                return $this->getE();
            case 'es':
                // First eccentricity squared.
                return $this->getES();
            case 'code':
                return $this->code;
            case 'name':
                return $this->name;
        }

        // TODO: exception - unknown property.
    }

    /**
     * Return as an array.
     * TODO: return somethign sensible for key 'ellps'.
     */
    public function asArray()
    {
        $result = [];

        if (isset($this->a)) {
            $result['a'] = $this->a;
        }

        if (isset($this->b)) {
            $result['b'] = $this->b;
        } elseif (isset($this->f)) {
            $result['f'] = $this->f;
        } elseif (isset($this->rf)) {
            $result['rf'] = $this->rf;
        }

        if (isset($this->code)) {
            $result['code'] = $this->code;
        }

        if (isset($this->name)) {
            $result['name'] = $this->name;
        }

        return $result;
    }

    /**
     * Magic getter access to the parameters.
     * TODO: handle 'ellps'.
     * TODO: with magic setters, this object is strictly no longer immutable.
     * Consider removing them.
     */
    /*
    public function __set($name, $value)
    {
        switch (strtolower($name)) {
            case 'a':
                $this->a = (float)$value;
                return;
            case 'b':
                $this->setB($value);
                return;
            case 'f':
                $this->setF($value);
                return;
            case 'rf':
                $this->setRF($value);
                return;
            case 'code':
                $this->code = $vslue;
                return;
            case 'name':
                $this->name = $value;
                return;
        }

        // TODO: exception - unknown property.
    }
    */
}

<?php

namespace yii\aerospike;

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Aerospike Cache implements a cache component using [aerospike](http://www.aerospike.com/) as the storage medium.
 * Aerospike Cache requires aerospike PHP Extension to work properly.
 *
 * @author Nicolae Serban <nicolae.serban@gmail.com>
 */
class Cache extends \yii\caching\Cache
{
    /** @var \yii\aerospike\Connection */
    public $aerospike = 'aerospike';

    /**
     * Initializes the aerospike Cache component.
     * This method will initialize the [[aerospike]] property to make sure it refers to a valid aerospike connection.
     * @throws InvalidConfigException if [[aerospike]] is invalid.
     */
    public function init()
    {
        parent::init();

        // Set serializer for aerospike
        $this->serializer = [
            function($value) { return $value[0]; },
            function($value) { return [$value, null]; }
        ];

        if (is_string($this->aerospike)) {
            $this->aerospike = Yii::$app->get($this->aerospike);
        } elseif (is_array($this->aerospike)) {
            if (!isset($this->aerospike['class'])) {
                $this->aerospike['class'] = Connection::className();
            }

            $this->aerospike = Yii::createObject($this->aerospike);
        }

        if (!$this->aerospike instanceof Connection) {
            throw new InvalidConfigException("Cache::aerospike must be either a Aerospike connection instance or the application component ID of a Aerospike connection.");
        }
    }

    /**
     * @param mixed $key
     * @return array
     */
    public function buildKey($key)
    {
        if ( !is_string($key) && !is_int($key)) {
            $key = md5(json_encode($key));
        }

        $ns = $this->aerospike->namespace;
        $set = $this->aerospike->set;

        return $this->aerospike->getConnection()->initKey($ns, $set, $this->keyPrefix . $key);
    }

    /**
     * @inheritdoc
     */
    protected function getValue($key)
    {
        $db = $this->aerospike->getConnection();
        $record = null;

        $db->get($key, $record);

        if ( is_null($record) || (is_array($record) && count($record) == 0))   {
            return false;
        } else {
            return $record['bins']['value'];
        }
    }

    /**
     * @inheritdoc
     */
    protected function setValue($key, $value, $expire)
    {
        $db = $this->aerospike->getConnection();
        $bins = [ 'value' => $value ];

        return ( $db->put($key, $bins, $expire) == 0) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function mset($items, $duration = 0, $dependency = null)
    {
        foreach ( $items as $key => $value ) {
            $key = $this->buildKey($key);
            $this->setValue($key, $value, $duration);
        }
    }

    /**
     * @inheritdoc
     */
    public function mget($keys)
    {
        $db = $this->aerospike->getConnection();

        $keyMap = [];
        foreach ($keys as $key) {
            $keyMap[$key] = $this->buildKey($key);
        }

        $keys = array_values($keyMap);

        $records = null;
        $db->getMany($keys, $records);

        $result = [];
        foreach ( $records as $key => $record ) {
            if ( $record == null )
                $result[$key] = null;
            else
                $result[$record['key']['key']] = $record['bins']['value'];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function addValue($key, $value, $expire)
    {
        return $this->setValue($key, $value, $expire);
    }

    /**
     * @inheritdoc
     */
    protected function deleteValue($key)
    {
        $db = $this->aerospike->getConnection();
        return ($db->remove($key) == 0 ) ? true : false;
    }

    /**
     * @inheritdoc
     */
    protected function flushValues()
    {
        throw new Exception('Action not supported by aerospike DB connection.');
    }
}

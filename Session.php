<?php

namespace yii\aerospike;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Aerospike Session implements a session component using [aerospike](http://www.aerospike.com/) as the storage medium.
 * Aerospike Session requires aerospike PHP Extension to work properly.
 *
 * @author Nicolae Serban <nicolae.serban@gmail.com>
 */
class Session extends \yii\web\Session
{
    /** @var \yii\aerospike\Connection */
    public $aerospike = 'aerospike';

    /** @var string */
    public $keyPrefix;

    /**
     * Initializes the aerospike Session component.
     * This method will initialize the [[aerospike]] property to make sure it refers to a valid aerospike connection.
     * @throws InvalidConfigException if [[aerospike]] is invalid.
     */
    public function init()
    {
        if (is_string($this->aerospike)) {
            $this->aerospike = Yii::$app->get($this->aerospike);
        } elseif (is_array($this->aerospike)) {
            if (!isset($this->aerospike['class'])) {
                $this->aerospike['class'] = Connection::className();
            }

            $this->aerospike = Yii::createObject($this->aerospike);
        }

        if (!$this->aerospike instanceof Connection) {
            throw new InvalidConfigException("Session::aerospike must be either a Redis connection instance or the application component ID of a Redis connection.");
        }

        if ($this->keyPrefix === null) {
            $this->keyPrefix = substr(md5(Yii::$app->id), 0, 5);
        }

        parent::init();
    }

    /**
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     * @return boolean whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return true;
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
        $key = $this->getKey($id);

        $record = null;
        $this->aerospike->getConnection()->get($key, $record);

        if ( is_null($record) || (is_array($record) && count($record) == 0))   {
            return '';
        } else {
            return base64_decode($record['bins']['value']);
        }
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return boolean whether session write is successful
     */
    public function writeSession($id, $data)
    {
        $db = $this->aerospike->getConnection();
        $key = $this->getKey($id);
        $bins = [ 'value' => base64_encode($data) ];

        return ( $db->put($key, $bins, $this->getTimeout()) == 0 ) ? true : false ;
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        $db = $this->aerospike->getConnection();
        $key = $this->getKey($id);

        return ($db->remove($key) == 0 ) ? true : false;
    }

    /**
     * Generates a unique key used for storing session data in cache.
     * @param string $id session variable name
     * @return string a safe cache key associated with the session variable name
     */
    protected function getKey($id)
    {
        $ns = $this->aerospike->namespace;
        $set = $this->aerospike->set;
        $key = $this->keyPrefix . md5(json_encode([__CLASS__, $id]));

        return $this->aerospike->getConnection()->initKey($ns, $set, $key);
    }
}

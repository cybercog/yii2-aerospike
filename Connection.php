<?php

namespace yii\aerospike;

use yii\base\Component;
use yii\db\Exception;

/**
 * Aerospike Connection requires aerospike PHP Extension to work properly.
 *
 * @author Nicolae Serban <nicolae.serban@gmail.com>
 */
class Connection extends Component
{
    /** @var array */
    public $config;

    /** @var string */
    public $namespace = 'test';

    /** @var string  */
    public $set = 'test';

    /** @var bool */
    public $persistent_connection = true;

    /** @var array */
    public $options = [];

    /** @var  \Aerospike */
    protected $connection = null;

    /**
     * @return \Aerospike
     * @throws Exception
     */
    public function getConnection()
    {
        if ( is_null($this->connection) ) {
            // Init connection
            $this->connection = new \Aerospike($this->config, $this->persistent_connection, $this->options);

            if ( $this->connection->errorno() != 0 ) {
                \Yii::error("Failed to open aerospike DB connection: ".$this->connection->errorno()." - " . $this->connection->error() , __CLASS__);
                throw new Exception('Failed to open aerospike DB connection', $this->connection->error(), (int) $this->connection->errorno());
            }
        }

        return $this->connection;
    }
}

<?php
// paloma


// for convenient use of same connection to return collections
//
class PalomaMongo {
	private static $database = 'testin_i_am';
	private static $connection;

	public static function collection( $collection_name ) {
		if ( !isset( $connection ) ) {
			$client = new MongoClient();
			$database_name = self::$database;
			self::$connection = $client->$database_name;
		}
		return new PalomaCollection( self::$connection, $collection_name );
	}
}


// to make sure that cursors return model objects
//
class PalomaCursor implements Iterator {

        protected $collection;
        protected $model_name;
        public $raw;

        public function __construct( $collection, $model_name, $cursor ) {
                $this->collection = $collection;
                $this->model_name = $model_name;
                $this->raw = $cursor;
        }

        public function wrap( $array ) {
                $model = new $this->model_name( $this->collection );
                $model->init( $array );
                return $model;
        }

        public function current() {
                return $this->wrap( $this->raw->current() );;
        }

        public function key() {
                return $this->raw->key();
        }

        public function rewind() {
                return $this->raw->rewind();
        }

        public function valid() {
                return $this->raw->valid();
        }

        public function next() {
                return $this->wrap( $this->raw->next() );
        }
}


// to make sure that find and findOne return model objects
//
class PalomaCollection {

        protected $model_name;
        public $raw;

        public function __construct( $connection, $collection_name ) {
                $this->raw = $connection->$collection_name;
                $this->model_name = ucwords( $collection_name );
        }

        public function find( $query ) {
                $cursor = $this->raw->find( $query );
                $paloma_cursor = new PalomaCursor( $this, $this->model_name, $cursor );
                return $paloma_cursor;
        }

        public function findOne( $query ) {
                $result = $this->raw->findOne( $query );
                $model = new $this->model_name( $this );
                $model->init( $result );
                return $model;
        }

}

// base model class with auto-validation on init and save
//
class PalomaModel {

        protected $data = [];
        protected $collection;

        public function __construct( $collection ) {
                $this->collection = $collection;
        }

        public function init( $data ) {
                if ( $this->validate( $data ) ) {
                        $this->data = $data;
                        return TRUE;
                }
                else {
                        return FALSE;
                }
        }

        public function validate( $data ) {
                return matchmaker\catches( $data, $this->schema );
        }

        public function save() {
                if ( $this->validate( $this->data ) ) {
                        $this->collection->raw->save( $this->data );
                        return TRUE;
                }
                else {
                        return FALSE;
                }
        }

        public function get( $location ) {
        	$data = new Dflydev\DotAccessData\Data( $this->data );
                //$data = new xmarcos\Dot\Container( $this->data );
                return $data->get( $location );
        }

        public function set( $location, $value ) {
        	$data = new Dflydev\DotAccessData\Data( $this->data )
                //$data = new xmarcos\Dot\Container( $this->data );
                $data->set( $location, $value );
                echo ( "set val is now: " . $data->get( $location ) );
                //$this->data = $data->all();
        }
}


?>

<?php
namespace app\models;

use flight\database\PdoWrapper;

class Document 
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $file_path;

    /**
     * @var string
     */
    public $original_name;

    /**
     * @var string
     */
    public $mime_type;

    /**
     * @var string
     */
    public $uploaded_at;

    
    /**
     * @var PdoWrapper
     */
    private $db;

    /**
     * Constructor
     */
    public function __construct(PdoWrapper $db)
    {
        $this->db = $db;
    }


    
}
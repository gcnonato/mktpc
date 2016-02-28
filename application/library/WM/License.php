<?php
class WM_License
    {
    private $allowedLocals = array("localhost", "127.0.0.1");
    private $license_live_days = 30;
    private $type = "themes";
    private $file_data = array("type" => "", "domain" => "", "expire" => "", "brand" => "", "check" => "");
    private $errors = array();
    private $license_file = "";
    public function __construct()
        {
        $this->license_file = BASE_PATH . "/cache/license.bin";
        $request            = JO_Request::getInstance();
        if (in_array($request->getDomain(), $this->allowedLocals))
            {
            return $this;
            } 
        else if ($request->issetQuery("update"))
            {
            if ($this->getLicense())
                {
                echo "License file was updated!";
                exit();
                }
            }
        else if ($request->issetQuery("upgrade"))
            {
            $this->upgradeCache();
            }
        else if ($request->issetQuery("upgrade_delete"))
            {
            $this->deleteUpgradeCahce();
            }
        }
    public function isValid()
        {
        return count($this->errors) == 0;
        }
    public function getErrors()
        {
        return count($this->errors) == 0;
        }


    static public function deleteUpgradeCahce()
        {
        @unlink(BASE_PATH . "/uploads/cache/items/" . date("Y/m") . "/cached.php");
        }
    }
?>
<?php

use Bitrix\Main\ModuleManager;

class dev_query extends CModule
{
    public $MODULE_ID = "rolan.query";
    public $MODULE_NAME = "Dev Query ORM";
    public $MODULE_DESCRIPTION = "Laravel-like query builder for Bitrix";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;

    public function __construct()
    {
        include __DIR__ . "/version.php";

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
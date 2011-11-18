<?php

namespace FOS\UserBundle\Resources\config;

class Settings {
  public $applicationTitle;
  public $adminEmail;
  public $baseLayout;
  public $usePageHeader;
  public $flashName;
  public $whyRegister;
  
  public function __construct($applicationTitle, $adminEmail, $baseLayout, $usePageHeader, $flashName, $whyRegister = null) {
    $this->applicationTitle = $applicationTitle;
    $this->adminEmail = $adminEmail;
    $this->baseLayout = $baseLayout;
    $this->usePageHeader = $usePageHeader;
    $this->flashName = $flashName;
    $this->whyRegister = $whyRegister;
  }
}
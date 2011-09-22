<?php

namespace FOS\UserBundle\Resources\config;

class Settings {
  public $applicationTitle;
  public $adminEmail;
  public $baseLayout;
  public $useBreadcrumb;
  public $flashName;
  
  public function __construct($applicationTitle, $adminEmail, $baseLayout, $useBreadcrumb, $flashName) {
    $this->applicationTitle = $applicationTitle;
    $this->adminEmail = $adminEmail;
    $this->baseLayout = $baseLayout;
    $this->useBreadcrumb = $useBreadcrumb;
    $this->flashName = $flashName;
  }
}
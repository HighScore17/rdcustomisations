<?php

function __is_development_enviroment() {
  return defined('DEVELOP_ENVIREMENT') && DEVELOP_ENVIREMENT === true;
}
<?php

namespace Fretelweb\PhpValidator;

class Validator{

   static public function Make($data,$rules,$message=[]){
      return new Validator($data,$rules,$message);
   }

   function __construct($data,$rules,$message=[]){

   }

   public function validate(){

   }
}

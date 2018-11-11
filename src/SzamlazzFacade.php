<?php 
namespace FerencBalogh\Szamlazzhu;

use Illuminate\Support\Facades\Facade;

class SzamlazzFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return Szamlazz::class;
    }
}

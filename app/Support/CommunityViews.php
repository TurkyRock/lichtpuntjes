<?php
namespace App\Support;
class CommunityViews {
 public static function name(string $name):string {return app()->getLocale()==='en'&&view()->exists('community.en.'.$name)?'community.en.'.$name:'community.'.$name;}
}

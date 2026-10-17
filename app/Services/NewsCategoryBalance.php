<?php
namespace App\Services;
use App\Models\Article;
class NewsCategoryBalance {
 public const CATEGORIES=['nature','energy','people','science','media','sport'];
 public function counts(bool $includeReady=false):array {
  $statuses=$includeReady?['published','news_ready','pending','error']:['published'];
  $totals=Article::where('origin','news')->whereNull('hidden_at')->whereIn('status',$statuses)->select('category')->selectRaw('count(*) as total')->groupBy('category')->pluck('total','category');
  return array_replace(array_fill_keys(self::CATEGORIES,0),$totals->only(self::CATEGORIES)->map(fn($count)=>(int)$count)->all());
 }
 public function prioritise(\Illuminate\Database\Eloquent\Builder $query):\Illuminate\Database\Eloquent\Builder {
  $counts=$this->counts();$categories=self::CATEGORIES;
  usort($categories,fn($a,$b)=>$counts[$a]<=>$counts[$b]);
  $sql='CASE category';$bindings=[];
  foreach($categories as $rank=>$category){$sql.=' WHEN ? THEN ?';$bindings[]=$category;$bindings[]=$rank;}
  return $query->orderByRaw($sql.' ELSE 6 END',$bindings);
 }
 public function allowed(string $category):bool {
  $counts=$this->counts();return isset($counts[$category])&&$counts[$category]<=min($counts);
 }
}

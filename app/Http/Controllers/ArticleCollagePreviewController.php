<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class ArticleCollagePreviewController extends Controller {
 public function __invoke(Request $r){abort_unless($r->user()?->is_admin,403);return view('community.collage-preview');}
}

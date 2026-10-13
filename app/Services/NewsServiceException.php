<?php
namespace App\Services;
// Only locally composed diagnostics, never raw provider responses or credentials.
class NewsServiceException extends \RuntimeException {}

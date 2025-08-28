<?php
add_filter('whitestudioteam_wcpa_allowed_mimes', function($m) {
  return [ 'pdf' => 'application/pdf' ]; 
});


add_filter('whitestudioteam_wcpa_max_upload_bytes', function() {
  return 2 * 1024 * 1024;
});
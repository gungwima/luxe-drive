<?php
// Cegah akses langsung ke folder config
http_response_code(403);
exit('Akses ditolak.');

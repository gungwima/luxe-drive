<?php
// Cegah akses langsung ke folder database
http_response_code(403);
exit('Akses ditolak.');

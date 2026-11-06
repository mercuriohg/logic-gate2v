<?php
// Carrega helpers e models. Incluído pelo RouterController e pelas views que consultam o banco.
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

require_once __DIR__ . '/../helpers/HttpError.php';
require_once __DIR__ . '/../helpers/Http.php';
require_once __DIR__ . '/../helpers/Session.php';
require_once __DIR__ . '/../helpers/Nivel.php';

require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/AdminModel.php';
require_once __DIR__ . '/../model/GateModel.php';
require_once __DIR__ . '/../model/PhaseModel.php';
require_once __DIR__ . '/../model/ProgressModel.php';

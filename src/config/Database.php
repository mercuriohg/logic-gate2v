<?php
class Database{
	private static $pdo;

	public static function getConnection(): PDO {
		if (self::$pdo === null) {
			$databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

			if ($databaseUrl) {
				$url = parse_url($databaseUrl);
				$host = $url['host'] ?? '';
				$port = $url['port'] ?? 3306;
				$database = isset($url['path']) ? ltrim($url['path'], '/') : '';
				$user = isset($url['user']) ? rawurldecode($url['user']) : '';
				$password = isset($url['pass']) ? rawurldecode($url['pass']) : '';
			} else {
				$host = getenv('DB_HOST') ?: 'mysql';
				$port = getenv('DB_PORT') ?: '3306';
				$database = getenv('DB_DATABASE') ?: 'logic_gate';
				$user = getenv('DB_USERNAME') ?: 'logic_gate_user';
				$password = getenv('DB_PASSWORD') ?: 'logic_gate_password';
			}

			$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
			$options = [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES => false,
			];

			if (filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN)) {
				$sslCa = getenv('DB_SSL_CA');
				if ($sslCa) {
					$options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
				}
			}

			self::$pdo = new PDO($dsn, $user, $password, $options);
		}

		return self::$pdo;
	}
}
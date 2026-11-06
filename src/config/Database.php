<?php
class Database{
	private static $pdo;

	public static function getConnection(): PDO {
		if (self::$pdo === null) {
			$host = getenv('DB_HOST') ?: 'mysql';
			$port = getenv('DB_PORT') ?: '3306';
			$database = getenv('DB_DATABASE') ?: 'logic_gate';
			$user = getenv('DB_USERNAME') ?: 'logic_gate_user';
			$password = getenv('DB_PASSWORD') ?: 'logic_gate_password';

			$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
			self::$pdo = new PDO($dsn, $user, $password, [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES => false,
			]);
		}

		return self::$pdo;
	}
}
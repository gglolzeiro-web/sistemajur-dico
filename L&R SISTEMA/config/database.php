<?php
/**
 * Configuração de conexão com o banco de dados.
 * No HostGator: crie o banco e um usuário via cPanel > MySQL Databases,
 * depois preencha os valores abaixo com os dados gerados lá.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'trocar_nome_do_banco');
define('DB_USER', 'trocar_usuario');
define('DB_PASS', 'trocar_senha');
define('DB_CHARSET', 'utf8mb4');

function getConexao(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Erro de conexão com o banco: ' . $e->getMessage());
            http_response_code(500);
            die('Não foi possível conectar ao banco de dados. Verifique config/database.php.');
        }
    }

    return $pdo;
}

<?php

namespace App\Models;

use Config\Database;
use PDO;
use PDOException;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    /**
     * Troca a senha do usuário
     *
     * @param int $id_usuario
     * @param string $senhaAtual
     * @param string $novaSenha
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword($id_usuario, $senhaAtual, $novaSenha)
    {
        try {
            $this->db->beginTransaction();

            // 1. Buscar senha atual
            $stmt = $this->db->prepare("SELECT senha_hash, id_login FROM usuario WHERE id_usuario = :id");
            $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                return ['success' => false, 'message' => 'Usuário não encontrado.'];
            }

            // 2. Verificar senha atual
            if (!password_verify($senhaAtual, $usuario['senha_hash'])) {
                return ['success' => false, 'message' => 'Senha atual incorreta.'];
            }

            // 3. Gerar hash da nova senha
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

            // 4. Atualizar senha em usuario
            $updateUsuario = $this->db->prepare("
            UPDATE usuario SET senha_hash = :novaSenha WHERE id_usuario = :id
        ");
            $updateUsuario->bindParam(':novaSenha', $novaSenhaHash);
            $updateUsuario->bindParam(':id', $id_usuario, PDO::PARAM_INT);
            $updateUsuario->execute();

            // 5. Atualizar senha também em login (se precisar manter)
            $updateLogin = $this->db->prepare("
            UPDATE login SET senha = :novaSenha WHERE id_login = :idLogin
        ");
            $updateLogin->bindParam(':novaSenha', $novaSenha); // ⚠ aqui está em texto puro
            $updateLogin->bindParam(':idLogin', $usuario['id_login'], PDO::PARAM_INT);
            $updateLogin->execute();

            $this->db->commit();
            return ['success' => true, 'message' => 'Senha alterada com sucesso.'];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erro no banco: ' . $e->getMessage()];
        }
    }
    public function updatePhoto($id_usuario, $fileName)
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE usuario 
            SET photo = :photo 
            WHERE id_usuario = :id
        ");
            $stmt->bindParam(':photo', $fileName, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);

            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Nenhuma linha atualizada'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }




}

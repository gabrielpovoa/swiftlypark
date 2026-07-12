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

    public function getUserById($id_usuario)
    {
        $stmt = $this->db->prepare("
            SELECT id_usuario, nome, email, photo 
            FROM usuario 
            WHERE id_usuario = :id
            LIMIT 1
        ");
        $stmt->bindParam(':id', $id_usuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Troca a senha do usuário (seguro)
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword($id_usuario, $senhaAtual, $novaSenha)
    {
        try {
            $this->db->beginTransaction();

            // 1) Buscar senha atual
            $stmt = $this->db->prepare("SELECT senha_hash, id_login FROM usuario WHERE id_usuario = :id");
            $stmt->execute([':id' => $id_usuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Usuário não encontrado.'];
            }

            // 2) Verificar se a senha ATUAL fornecida está correta
            if (!password_verify($senhaAtual, $usuario['senha_hash'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Senha atual incorreta.'];
            }

            // --- NOVA TRAVA DE SEGURANÇA ---
            // 3) Verificar se a NOVA senha é igual à atual
            if (password_verify($novaSenha, $usuario['senha_hash'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'A nova senha não pode ser igual à senha atual.'];
            }
            // ------------------------------

            // 4) Gerar hash da nova senha
            $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

            // 5) Atualizar usuario
            $updateUsuario = $this->db->prepare("UPDATE usuario SET senha_hash = :novaSenha WHERE id_usuario = :id");
            $updateUsuario->execute([
                ':novaSenha' => $novaSenhaHash,
                ':id' => $id_usuario
            ]);

            // 6) Atualizar login também com HASH (mantém consistência)
            $updateLogin = $this->db->prepare("UPDATE login SET senha = :novaSenha WHERE id_login = :idLogin");
            $updateLogin->execute([
                ':novaSenha' => $novaSenhaHash,
                ':idLogin' => $usuario['id_login']
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Senha alterada com sucesso.'];

        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
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
            $stmt->execute([
                ':photo' => $fileName,
                ':id' => $id_usuario
            ]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Nenhuma linha atualizada'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateProfile(int $idUsuario, string $nome, string $email): array
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE usuario
                 SET nome = :nome, email = :email
                 WHERE id_usuario = :id'
            );
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':id' => $idUsuario,
            ]);

            return [
                'success' => $stmt->rowCount() > 0,
                'message' => $stmt->rowCount() > 0
                    ? 'Perfil atualizado com sucesso.'
                    : 'Nenhuma alteração foi aplicada.',
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar o perfil: ' . $e->getMessage()];
        }
    }
}

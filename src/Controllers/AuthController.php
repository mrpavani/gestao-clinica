<?php
// src/Controllers/AuthController.php

require_once __DIR__ . '/../Database.php';

class AuthController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Authenticate user and create session
     */
    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login time
            $updateStmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            // Create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['professional_id'] = $user['professional_id'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Logout user and destroy session
     */
    public function logout() {
        session_destroy();
        session_start(); // Restart for flash messages if needed
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current logged-in user data
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'professional_id' => $_SESSION['professional_id']
        ];
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin() {
        return self::isAuthenticated() && $_SESSION['role'] === 'admin';
    }

    /**
     * Check if current user is professional
     */
    public static function isProfessional() {
        return self::isAuthenticated() && $_SESSION['role'] === 'professional';
    }

    /**
     * Create new user (admin only)
     */
    public function createUser($username, $password, $role, $professional_id = null) {
        if (!self::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado. Apenas administradores podem criar usuários.'];
        }

        // Validate inputs
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Usuário e senha são obrigatórios.'];
        }

        if ($role === 'professional' && empty($professional_id)) {
            return ['success' => false, 'message' => 'Profissional deve ser selecionado para usuários do tipo profissional.'];
        }

        // Check if username already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Nome de usuário já existe.'];
        }

        // Create user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password_hash, role, professional_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        
        try {
            $stmt->execute([$username, $password_hash, $role, $professional_id]);
            return ['success' => true, 'message' => 'Usuário criado com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar usuário: ' . $e->getMessage()];
        }
    }

    /**
     * Get all users (admin only)
     */
    public function getAllUsers() {
        if (!self::isAdmin()) {
            return [];
        }

        $sql = "SELECT u.*, p.name as professional_name 
                FROM users u 
                LEFT JOIN professionals p ON u.professional_id = p.id 
                ORDER BY u.created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Delete user (admin only)
     */
    public function deleteUser($user_id) {
        if (!self::isAdmin()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        // Prevent admin from deleting themselves
        if ($user_id == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'Você não pode excluir sua própria conta.'];
        }

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        try {
            $stmt->execute([$user_id]);
            return ['success' => true, 'message' => 'Usuário excluído com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao excluir usuário.'];
        }
    }

    /**
     * Change password
     */
    public function changePassword($user_id, $current_password, $new_password) {
        // Users can only change their own password unless they're admin
        if (!self::isAdmin() && $user_id != $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        // Verify current password if it's the user changing their own
        if ($user_id == $_SESSION['user_id']) {
            $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!password_verify($current_password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Senha atual incorreta.'];
            }
        }

        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        
        try {
            $stmt->execute([$password_hash, $user_id]);
            return ['success' => true, 'message' => 'Senha alterada com sucesso!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao alterar senha.'];
        }
    }
}

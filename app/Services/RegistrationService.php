<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\ValidationException;
use App\Repositories\UserRepository;
use PDO;
use PDOException;
use Throwable;

final class RegistrationService
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public static function slug(string $value): string
    {
        $slug = mb_strtolower(trim($value), 'UTF-8');
        $slug = strtr($slug, [
            'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
        ]);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        return trim(substr($slug, 0, 120), '-');
    }

    public function register(array $data, string $ip): void
    {
        $slug = self::slug($data['slug'] ?: $data['nome_fantasia']);
        if (strlen($slug) < 3) {
            throw new ValidationException(['slug'=>'Escolha um identificador público com pelo menos 3 caracteres.']);
        }
        $this->db->beginTransaction();
        try {
            $company = $this->db->prepare('INSERT INTO empresas (nome,nome_fantasia,slug,segmento,telefone,whatsapp,email,cor_principal,timezone) VALUES (:nome,:fantasia,:slug,:segmento,:telefone,:whatsapp,:email,:cor,:timezone)');
            $company->execute(['nome'=>$data['nome'],'fantasia'=>$data['nome_fantasia'],'slug'=>$slug,'segmento'=>$data['segmento'],'telefone'=>$data['telefone'] ?: null,'whatsapp'=>$data['whatsapp'] ?: null,'email'=>strtolower($data['email']),'cor'=>'#635bff','timezone'=>'America/Sao_Paulo']);
            $tenantId = (int) $this->db->lastInsertId();
            $this->db->prepare('INSERT INTO configuracoes_empresa (empresa_id) VALUES (:empresa)')->execute(['empresa'=>$tenantId]);
            $user = $this->db->prepare("INSERT INTO usuarios (empresa_id,nome,email,senha_hash,perfil) VALUES (:empresa,:nome,:email,:senha,'proprietario')");
            $user->execute(['empresa'=>$tenantId,'nome'=>$data['proprietario_nome'],'email'=>strtolower($data['email']),'senha'=>password_hash($data['senha'], PASSWORD_DEFAULT)]);
            $userId = (int) $this->db->lastInsertId();
            (new AuditService())->record($tenantId,$userId,'empresa.criada','empresa',$tenantId,['slug'=>$slug],$ip);
            $this->db->commit();
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            if ((string)$exception->getCode() === '23000') {
                throw new ValidationException(['cadastro'=>'Este e-mail ou endereço público já está em uso.']);
            }
            throw $exception;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            throw $exception;
        }
        $user = (new UserRepository())->findForLogin(strtolower($data['email']));
        if (!$user) { throw new ValidationException(['cadastro'=>'Conta criada, mas não foi possível iniciar a sessão.']); }
        Auth::login($user);
    }
}

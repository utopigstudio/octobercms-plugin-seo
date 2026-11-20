<?php namespace Utopigs\Seo\Updates;

use Db;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        $this->grant('backend_user_roles');
        $this->grant('backend_users');
    }

    public function down()
    {
        // This update is irreversible
    }

    protected function grant(string $table): void
    {
        foreach ($this->permissionRows($table) as $row) {
            $permissions = $this->decodePermissions($row->permissions);
            if ($permissions === null || array_key_exists('utopigs.seo.manage.list', $permissions)) {
                continue;
            }

            if (!$this->grantsManage($permissions)) {
                continue;
            }

            $permissions['utopigs.seo.manage.list'] = 1;
            $this->storePermissions($table, $row->id, $permissions);
        }
    }

    protected function permissionRows(string $table)
    {
        $query = Db::table($table)->select('id', 'permissions');

        if ($table === 'backend_user_roles') {
            $query->where(function ($query) {
                $query->where('is_system', false)->orWhereNull('is_system');
            })->where(function ($query) {
                $query->whereNull('code')
                    ->orWhereNotIn('code', ['developer', 'publisher']);
            });
        }

        return $query->get();
    }

    protected function grantsManage(array $permissions): bool
    {
        if ($this->deniesManage($permissions)) {
            return false;
        }

        if ((int) ($permissions['utopigs.seo.manage'] ?? 0) === 1) {
            return true;
        }

        foreach ($permissions as $permission => $value) {
            if ((int) $value !== 1 || !is_string($permission) || !str_ends_with($permission, '*')) {
                continue;
            }

            $prefix = substr($permission, 0, -1);
            if ($prefix !== 'utopigs.seo.manage' && str_starts_with('utopigs.seo.manage', $prefix)) {
                return true;
            }
        }

        return false;
    }

    protected function deniesManage(array $permissions): bool
    {
        return (int) ($permissions['utopigs.seo.manage'] ?? 0) === -1;
    }

    protected function decodePermissions($permissions): ?array
    {
        if (!is_string($permissions) || $permissions === '') {
            return null;
        }

        $decoded = json_decode($permissions, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function storePermissions(string $table, $id, array $permissions): void
    {
        Db::table($table)->where('id', $id)->update([
            'permissions' => $permissions ? json_encode($permissions, JSON_UNESCAPED_UNICODE) : '',
        ]);
    }
};

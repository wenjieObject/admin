<?php

namespace Tests\Feature;

use App\Models\AdminPermission;
use App\Models\AdminRole;
use Tests\AdminTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\RequestActions;

class AdminRoleControllerTest extends AdminTestCase
{
    use RefreshDatabase;
    use RequestActions;
    use WithFaker;
    protected $resourceName = 'admin-roles';

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testStoreValidation()
    {
        // name, slug required
        // permissions array
        $res = $this->storeResource([
            'name' => '',
            'slug' => '',
            'permissions' => 'not array',
        ]);
        $res->assertJsonValidationErrors(['name', 'slug', 'permissions']);

        factory(AdminRole::class)->create([
            'name' => 'name',
            'slug' => 'slug',
        ]);
        factory(AdminPermission::class)->create();
        // name, slug unique
        // permissions.* exists
        $res = $this->storeResource([
            'name' => 'name',
            'slug' => 'slug',
            'permissions' => [99],
        ]);
        $res->assertJsonValidationErrors(['name', 'slug', 'permissions']);
    }

    public function testStore()
    {
        $inputs = [
            'name' => 'name',
            'slug' => 'slug',
        ];
        $res = $this->storeResource($inputs);
        $res->assertStatus(201);
        $this->assertDatabaseHas('admin_roles', $inputs + ['id' => 1]);

        AdminRole::truncate();
        $res = $this->storeResource($inputs + [
                'permissions' => [factory(AdminPermission::class)->create()->id],
            ]);
        $res->assertStatus(201);

        $this->assertDatabaseHas('admin_permission_role', [
            'role_id' => 1,
            'permission_id' => 1,
        ]);
    }

    public function testEdit()
    {
        $this->createRole();

        $res = $this->editResource(1);
        $res->assertStatus(200)
            ->assertJson(AdminRole::first()->toArray())
            ->assertJsonCount(1, 'permissions');
    }

    protected function createRole()
    {
        factory(AdminRole::class)->create()
            ->permissions()
            ->createMany([factory(AdminPermission::class)->make()->toArray()]);
    }
}
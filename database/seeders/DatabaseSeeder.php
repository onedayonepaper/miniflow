<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\RequestTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Groups (Department 모델을 그룹으로 활용)
        $community = Department::create([
            'name' => '예시 커뮤니티',
            'code' => 'COMM',
            'sort_order' => 1,
        ]);

        $operations = Department::create([
            'name' => '운영팀',
            'code' => 'OPS',
            'parent_id' => $community->id,
            'sort_order' => 1,
        ]);

        $members = Department::create([
            'name' => '일반 멤버',
            'code' => 'MEM',
            'parent_id' => $community->id,
            'sort_order' => 2,
        ]);

        // 2. Create Users
        $admin = User::create([
            'name' => '홍길동',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'department_id' => $community->id,
            'position' => '관리자',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $manager = User::create([
            'name' => '김철수',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'department_id' => $operations->id,
            'position' => '담당자',
            'role' => 'approver',
            'email_verified_at' => now(),
        ]);

        $user = User::create([
            'name' => '이영희',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'department_id' => $members->id,
            'position' => '멤버',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Update group managers
        $community->update(['manager_id' => $admin->id]);
        $operations->update(['manager_id' => $manager->id]);

        // 3. Create Request Templates
        RequestTemplate::create([
            'name' => '신청서',
            'type' => 'standard',
            'description' => '제목, 내용, 첨부파일을 포함한 기본 신청 양식',
            'schema' => [
                'fields' => [
                    [
                        'name' => 'title',
                        'label' => '제목',
                        'type' => 'text',
                        'required' => true,
                        'maxLength' => 100,
                    ],
                    [
                        'name' => 'content',
                        'label' => '내용',
                        'type' => 'textarea',
                        'required' => true,
                        'maxLength' => 2000,
                    ],
                    [
                        'name' => 'attachment',
                        'label' => '첨부파일',
                        'type' => 'file',
                        'required' => false,
                    ],
                ],
            ],
            'default_approval_line' => [
                'steps' => [
                    ['step' => 1, 'type' => 'approver', 'label' => '담당자 승인'],
                ],
            ],
            'created_by' => $admin->id,
        ]);

        RequestTemplate::create([
            'name' => '간편 양식',
            'type' => 'simple',
            'description' => '간단한 요청을 위한 단순 양식',
            'schema' => [
                'fields' => [
                    [
                        'name' => 'content',
                        'label' => '요청 내용',
                        'type' => 'textarea',
                        'required' => true,
                        'maxLength' => 1000,
                    ],
                ],
            ],
            'default_approval_line' => [
                'steps' => [
                    ['step' => 1, 'type' => 'approver', 'label' => '승인'],
                ],
            ],
            'created_by' => $admin->id,
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Demo accounts:');
        $this->command->info('  Admin:   admin@example.com / password');
        $this->command->info('  Manager: manager@example.com / password');
        $this->command->info('  User:    user@example.com / password');
    }
}

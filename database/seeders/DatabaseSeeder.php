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
        // 1. Create Departments
        $headquarters = Department::create([
            'name' => '경영지원본부',
            'code' => 'HQ',
            'sort_order' => 1,
        ]);

        $hrTeam = Department::create([
            'name' => '인사팀',
            'code' => 'HR',
            'parent_id' => $headquarters->id,
            'sort_order' => 1,
        ]);

        $devHeadquarters = Department::create([
            'name' => '개발본부',
            'code' => 'DEV',
            'sort_order' => 2,
        ]);

        $dev1Team = Department::create([
            'name' => '개발1팀',
            'code' => 'DEV1',
            'parent_id' => $devHeadquarters->id,
            'sort_order' => 1,
        ]);

        $dev2Team = Department::create([
            'name' => '개발2팀',
            'code' => 'DEV2',
            'parent_id' => $devHeadquarters->id,
            'sort_order' => 2,
        ]);

        // 2. Create Users
        $admin = User::create([
            'name' => '관리자',
            'email' => 'admin@miniflow.test',
            'password' => Hash::make('password'),
            'department_id' => $headquarters->id,
            'position' => '본부장',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $director = User::create([
            'name' => '박부서장',
            'email' => 'director@miniflow.test',
            'password' => Hash::make('password'),
            'department_id' => $devHeadquarters->id,
            'position' => '본부장',
            'role' => 'approver',
            'email_verified_at' => now(),
        ]);

        $manager = User::create([
            'name' => '김팀장',
            'email' => 'manager@miniflow.test',
            'password' => Hash::make('password'),
            'department_id' => $dev1Team->id,
            'position' => '팀장',
            'role' => 'approver',
            'email_verified_at' => now(),
        ]);

        $user = User::create([
            'name' => '이사원',
            'email' => 'user@miniflow.test',
            'password' => Hash::make('password'),
            'department_id' => $dev1Team->id,
            'position' => '사원',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Update department managers
        $devHeadquarters->update(['manager_id' => $director->id]);
        $dev1Team->update(['manager_id' => $manager->id]);

        // 3. Create Request Templates
        RequestTemplate::create([
            'name' => '휴가신청',
            'type' => 'leave',
            'description' => '연차, 반차, 병가 등 휴가 신청',
            'schema' => [
                'fields' => [
                    [
                        'name' => 'leave_type',
                        'label' => '휴가 종류',
                        'type' => 'select',
                        'options' => ['연차', '반차(오전)', '반차(오후)', '병가', '경조사'],
                        'required' => true,
                    ],
                    [
                        'name' => 'start_date',
                        'label' => '시작일',
                        'type' => 'date',
                        'required' => true,
                    ],
                    [
                        'name' => 'end_date',
                        'label' => '종료일',
                        'type' => 'date',
                        'required' => true,
                    ],
                    [
                        'name' => 'reason',
                        'label' => '사유',
                        'type' => 'textarea',
                        'required' => true,
                        'maxLength' => 500,
                    ],
                ],
            ],
            'default_approval_line' => [
                'steps' => [
                    ['step' => 1, 'type' => 'team_leader', 'label' => '팀장 승인'],
                    ['step' => 2, 'type' => 'department_head', 'label' => '부서장 승인'],
                ],
            ],
            'created_by' => $admin->id,
        ]);

        RequestTemplate::create([
            'name' => '지출결의',
            'type' => 'expense',
            'description' => '업무 관련 비용 지출 요청',
            'schema' => [
                'fields' => [
                    [
                        'name' => 'expense_type',
                        'label' => '지출 항목',
                        'type' => 'select',
                        'options' => ['회의비', '교통비', '사무용품', '교육비', '기타'],
                        'required' => true,
                    ],
                    [
                        'name' => 'amount',
                        'label' => '금액',
                        'type' => 'number',
                        'required' => true,
                        'min' => 1000,
                    ],
                    [
                        'name' => 'expense_date',
                        'label' => '지출일',
                        'type' => 'date',
                        'required' => true,
                    ],
                    [
                        'name' => 'description',
                        'label' => '상세 내용',
                        'type' => 'textarea',
                        'required' => true,
                        'maxLength' => 1000,
                    ],
                ],
            ],
            'default_approval_line' => [
                'steps' => [
                    ['step' => 1, 'type' => 'team_leader', 'label' => '팀장 승인'],
                    ['step' => 2, 'type' => 'department_head', 'label' => '부서장 승인'],
                ],
            ],
            'created_by' => $admin->id,
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Demo accounts:');
        $this->command->info('  Admin:    admin@miniflow.test / password');
        $this->command->info('  Manager:  manager@miniflow.test / password');
        $this->command->info('  Director: director@miniflow.test / password');
        $this->command->info('  User:     user@miniflow.test / password');
    }
}

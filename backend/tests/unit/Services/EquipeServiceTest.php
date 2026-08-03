<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\EquipeRepository;
use App\Services\EquipeService;
use CodeIgniter\Shield\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

/**
 * @internal
 */
final class EquipeServiceTest extends CIUnitTestCase
{
    private EquipeRepository $repository;
    private EquipeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(EquipeRepository::class);
        $this->service = new EquipeService($this->repository);
    }

    protected function tearDown(): void
    {
        Services::resetSingle('auth');
        parent::tearDown();
    }

    public function testCreateWithExistingUserIdLinksUser(): void
    {
        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $data): bool {
                return $data['user_id'] === 42 && $data['equ_nome'] === 'Dra. Ana';
            }))
            ->willReturn(1);

        $result = $this->service->create(['user_id' => 42, 'equ_nome' => 'Dra. Ana']);

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['id']);
    }

    public function testCreateWithoutUserIdOrEmailLeavesUserIdNull(): void
    {
        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static fn (array $data): bool => $data['user_id'] === null))
            ->willReturn(2);

        $result = $this->service->create(['equ_nome' => 'Recepção']);

        $this->assertSame('success', $result['status']);
    }

    public function testCreateWithEmailCreatesShieldUserAndLinksIt(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class))
            ->willReturn(true);
        $userModel->method('getInsertID')->willReturn(99);

        $userEntity = $this->createMock(User::class);
        $userEntity->expects($this->once())->method('addGroup')->with('equipe');
        $userModel->expects($this->once())->method('find')->with(99)->willReturn($userEntity);

        $auth = $this->createMock(Auth::class);
        $auth->method('setAuthenticator')->willReturn($auth);
        $auth->method('getProvider')->willReturn($userModel);
        Services::injectMock('auth', $auth);

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (array $data): bool {
                return $data['user_id'] === 99 && $data['equ_nome'] === 'Dr. João';
            }))
            ->willReturn(3);

        $result = $this->service->create([
            'equ_nome' => 'Dr. João',
            'email'    => 'joao@example.com',
            'password' => 'S3nha@123',
        ]);

        $this->assertSame('success', $result['status']);
        $this->assertSame(3, $result['id']);
    }

    public function testCreateReturnsErrorWhenShieldUserCreationFails(): void
    {
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('save')->willReturn(false);
        $userModel->method('errors')->willReturn(['email' => 'Email já cadastrado']);

        $auth = $this->createMock(Auth::class);
        $auth->method('setAuthenticator')->willReturn($auth);
        $auth->method('getProvider')->willReturn($userModel);
        Services::injectMock('auth', $auth);

        $this->repository->expects($this->never())->method('create');

        $result = $this->service->create([
            'equ_nome' => 'Dr. João',
            'email'    => 'joao@example.com',
        ]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao criar usuário: Email já cadastrado', $result['message']);
    }

    public function testCreateReturnsErrorWhenRepositoryFailsToPersist(): void
    {
        $this->repository->method('create')->willReturn(0);

        $result = $this->service->create(['user_id' => 5, 'equ_nome' => 'Dra. Ana']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao processar o cadastro da equipe', $result['message']);
    }

    public function testGetUserNameAndHonorificDelegatesToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('getUserNameAndHonorific')
            ->with(7)
            ->willReturn(['nome' => 'Ana', 'pronome' => 'Dra.']);

        $this->assertSame(['nome' => 'Ana', 'pronome' => 'Dra.'], $this->service->getUserNameAndHonorific(7));
    }
}

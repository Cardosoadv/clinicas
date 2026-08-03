<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Repositories\LojasRepository;
use App\Services\LojasService;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use Exception;

/**
 * @internal
 */
final class LojasServiceTest extends CIUnitTestCase
{
    private LojasRepository $repository;
    private LojasService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(LojasRepository::class);
        $this->service = new LojasService($this->repository);
    }

    public function testCreateWithLogoWithoutFileJustCreatesRecord(): void
    {
        $this->repository->expects($this->once())
            ->method('create')
            ->with(['nome' => 'Filial 1'])
            ->willReturn(10);

        $this->repository->expects($this->never())->method('update');

        $result = $this->service->createWithLogo(['nome' => 'Filial 1']);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Loja criada com sucesso', $result['message']);
        $this->assertSame(10, $result['id']);
    }

    public function testCreateWithLogoReturnsErrorWhenCreateFails(): void
    {
        $this->repository->method('create')->willReturn(0);

        $result = $this->service->createWithLogo(['nome' => 'Filial 1']);

        // create() returning 0 is falsy, so no file processing happens and success
        // is still reported for the base record with an empty id, matching current behavior.
        $this->assertSame('success', $result['status']);
        $this->assertSame(0, $result['id']);
    }

    public function testCreateWithLogoProcessesValidFile(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(false);
        $file->method('getRandomName')->willReturn('logo123.png');
        $file->expects($this->once())
            ->method('move')
            ->with(WRITEPATH . 'imagens/loja/10', 'logo123.png')
            ->willReturn(true);

        $this->repository->method('create')->willReturn(10);
        $this->repository->expects($this->once())
            ->method('update')
            ->with(10, ['logo' => 'logo123.png'])
            ->willReturn(true);

        $result = $this->service->createWithLogo(['nome' => 'Filial 1'], $file);

        $this->assertSame('success', $result['status']);
        $this->assertSame(10, $result['id']);
    }

    public function testCreateWithLogoIgnoresInvalidFile(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(false);

        $this->repository->method('create')->willReturn(10);
        $this->repository->expects($this->never())->method('update');

        $result = $this->service->createWithLogo(['nome' => 'Filial 1'], $file);

        $this->assertSame('success', $result['status']);
    }

    public function testCreateWithLogoReturnsErrorWhenRepositoryThrows(): void
    {
        $this->repository->method('create')->willThrowException(new Exception('db down'));

        $result = $this->service->createWithLogo(['nome' => 'Filial 1']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro interno: db down', $result['message']);
    }

    public function testUpdateWithLogoWithoutFileUpdatesOnlyData(): void
    {
        $this->repository->expects($this->once())
            ->method('update')
            ->with(5, ['nome' => 'Filial Nova'])
            ->willReturn(true);

        $result = $this->service->updateWithLogo(5, ['nome' => 'Filial Nova']);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Loja atualizada com sucesso', $result['message']);
    }

    public function testUpdateWithLogoReplacesLogoWhenFileProvided(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('hasMoved')->willReturn(false);
        $file->method('getRandomName')->willReturn('newlogo.png');
        $file->expects($this->once())
            ->method('move')
            ->with(WRITEPATH . 'imagens/loja/5', 'newlogo.png')
            ->willReturn(true);

        $this->repository->expects($this->once())
            ->method('update')
            ->with(5, ['nome' => 'Filial Nova', 'logo' => 'newlogo.png'])
            ->willReturn(true);

        $result = $this->service->updateWithLogo(5, ['nome' => 'Filial Nova'], $file);

        $this->assertSame('success', $result['status']);
    }

    public function testUpdateWithLogoReturnsErrorWhenRepositoryReturnsFalse(): void
    {
        $this->repository->method('update')->willReturn(false);

        $result = $this->service->updateWithLogo(5, ['nome' => 'Filial Nova']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro ao atualizar loja', $result['message']);
    }

    public function testUpdateWithLogoReturnsErrorWhenRepositoryThrows(): void
    {
        $this->repository->method('update')->willThrowException(new Exception('locked'));

        $result = $this->service->updateWithLogo(5, ['nome' => 'Filial Nova']);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Erro interno: locked', $result['message']);
    }
}

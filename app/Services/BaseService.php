<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    protected BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all records
     */
    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    /**
     * Get record by ID
     */
    public function getById(int $id): ?Model
    {
        return $this->repository->find($id);
    }

    /**
     * Get record by ID or fail
     */
    public function getByIdOrFail(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * Create new record
     */
    public function create(array $data): Model
    {
        $validatedData = $this->validateData($data);
        return $this->repository->create($validatedData);
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): bool
    {
        $validatedData = $this->validateData($data);
        return $this->repository->update($id, $validatedData);
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Get paginated records
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Validate data before processing
     * Override this method in child classes for specific validation
     */
    protected function validateData(array $data): array
    {
        return $data;
    }

    /**
     * Handle business logic before create
     * Override this method in child classes
     */
    protected function beforeCreate(array &$data): void
    {
        // Override in child classes
    }

    /**
     * Handle business logic after create
     * Override this method in child classes
     */
    protected function afterCreate(Model $model): void
    {
        // Override in child classes
    }
}

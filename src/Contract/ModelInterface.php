<?php
namespace Juzdy\Contract;

use Juzdy\Model\CollectionInterface;

interface ModelInterface extends \ArrayAccess
{
    //@todo define methods

    /**
     * Get the name of the table associated with this model.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Describe the fields in the table.
     *
     * @return array
     */
    public function describe(): array;

    /**
     * Get the primary key field name.
     *
     * @return string
     */
    public function getPrimaryKey(): string;

    /**
     * Get the collection associated with this model.
     *
     * @return CollectionInterface
     */
    public function getCollection(?int $itemMode = null): CollectionInterface;

    /**
     * Get the primary key of the model.
     *
     * @return string
     */
    public function getId(): ?int;

    /**
     * Find a record by its ID.
     *
     * @param int $id The ID of the record to find
     * @return static
     */
    public static function find(int $id): static;

    /**
     * Get all records from the table.
     *
     * @return array
     */
    public function allAsArray(): array;



    /**
     * Load a record by its ID.
     *
     * @param int $id The ID of the record to load
     * @return static
     */
    public function load(int $id): static;

    /**
     * Check if the model is loaded with data.
     *
     * @return bool
     */
    public function isLoaded(): bool;

    /**
     * Get the data of the model.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Set data for the model.
     *
     * @param array $data The data to set
     * @return static
     */
    public function setData(array $data): static;

    public function has(string $field): bool;
    /**
     * Get a specific field value.
     *
     * @param string $field The field name
     * @return mixed|null The value of the field or null if it doesn't exist
     */
    public function get(string $field): mixed;

    /**
     * Set a specific field value.
     *
     * @param string $field The field name
     * @param mixed $value The value to set
     * @return static
     * @throws \Exception If the field does not exist in the table
     */
    public function set(string $field, mixed $value): static;
    
    /**
     * Save the model data to the database.
     *
     * If the model is loaded, it will update the existing record.
     * If not, it will create a new record.
     *
     * @return static
     */
    public function save(): static;

    /**
     * Create a new record in the database.
     *
     * @param array $data The data to insert
     * @return static
     * @throws \Exception If there is an error during insertion
     */
    public function create(array $data): static;

    /**
     * Update an existing record in the database.
     *
     * @param array $data The data to update
     * @return static
     * @throws \Exception If there is no primary key value set or if there is no data to update
     */
    public function update(array $data): static;

    /**
     * Delete a record by its ID.
     *
     * @param int $id The ID of the record to delete
     * @return bool Returns true if the deletion was successful
     */
    public function delete(int $id): static;
}
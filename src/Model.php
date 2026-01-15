<?php
namespace Juzdy;

use Juzdy\Contract\ModelInterface;
use Juzdy\Model\Collection;
use Juzdy\Model\CollectionInterface;
use PDO;

abstract class Model implements ModelInterface
{
    protected string $table;
    protected array $data = [];
    protected ?array $fields = null;
    protected string $primaryKey = 'id';
    //protected array $foreignKeys = [];
    private ?CollectionInterface $collection = null;
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Magic method to get or set properties dynamically.
     *
     * @param string $key The property name
     * @param mixed|null $value The value to set (if null, it will get the value)
     * @return mixed
     */
    public function __invoke(string $key, ?string $value = null)
    {
        if ($value === null) {
            return $this->get($key);
        }

        return $this->set($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
      * {@inheritDoc}
     */
    public static function find(int $id): static
    {
        $instance = new static();
        return $instance->load($id);
    }


    /**
     * {@inheritDoc}
     */
    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
    * {@inheritDoc}
     */
    public function describe(): array
    {
        if ($this->fields === null) {
            $stmt = $this->db->query("DESCRIBE {$this->table}");
            $this->fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        return $this->fields;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): ?int
    {
        return (int)($this->data[$this->primaryKey] ?? null);
    }

    /**
     * Get the primary key field name.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
    * {@inheritDoc}
     */
    public function getCollection(?int $itemMode = null): Collection
    {
        if (!$this->collection instanceof Collection) {
            
            $collectionClass = class_exists(
                static::class . '\Collection',
                true
            )
                ? static::class . '\Collection'
                : Collection::class;

            $this->collection = new $collectionClass(static::class);
            
            if ($itemMode !== null) {
                $this->collection->setItemMode($itemMode);
            }
        }

        return $this->collection;    
    }

    /**
     * Get all records from the table.
     *
     * @return array
     */
    public function allAsArray(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->query($sql)->fetchAll();
    }



    /**
     * {@inheritDoc}
     */
    public function load(int $id): static
    {
        $this->_beforeLoad($id);

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $this->data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $this->_afterLoad($id);

        return $this;
    }

    /**
     * Hook for custom logic before loading the model.
     *
     * @param int $id The ID of the record to load
     */
    protected function _beforeLoad(int $id): void
    {
        // Hook for custom logic before loading the model
    }

    /**
     * Hook for custom logic after loading the model.
     *
     * @param int $id The ID of the record that was loaded
     */
    protected function _afterLoad(int $id): void
    {
        // Hook for custom logic after loading the model
    }

    /**
     * Check if the model is loaded with data.
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return !empty($this->data) && isset($this->data[$this->getPrimaryKey()]);
    }

    /**
     * Get the data of the model.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function setData(array $data): static
    {
        //unset($data[$this->primaryKey]); // Remove primary key if present

        $this->data = array_replace($this->data, $data);

        return $this;
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $field, mixed $value): static
    {
        if (!in_array($field, $this->describe())) {
            throw new \Exception("Field $field does not exist in table {$this->table}");
        }
        $this->data[$field] = $value;
        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    public function save(): static
    {
        $this->_beforeSave();

        unset($this->data['created_at']);
        unset($this->data['created_by']);
        // unset($this->data['updated_by']);
        // unset($this->data['updated_at']);

        if ($this->isLoaded()) {
            $this->update($this->data);
        } else {
            $this->create($this->data);
        }

        $this->_afterSave();

        return $this;
    }

    /**
     * Hook for custom logic after saving the model.
     */
    protected function _beforeSave(): void
    {
        // Hook for custom logic before saving the model
    }

    /**
     * Hook for custom logic after saving the model.
     *
     * This can be used to perform actions that should occur after the model is saved,
     * such as clearing caches or triggering events.
     */
    protected function _afterSave(): void
    {
        // Hook for custom logic after saving the model
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): static
    {
        unset($data[$this->primaryKey]);

        $this->_beforeCreate($data);

        foreach ($data as $key => $value) {
            if (empty($value)) {
                unset($data[$key]);
                continue;
            }
            if (!in_array($key, $this->describe())) {
                unset($data[$key]);
                continue;
            }
            if (!in_array($key, $this->describe())) {
                unset($data[$key]);
                continue;
            }
            if (is_array($value)) {
                $data[$key] = json_encode($value);
            }
        }

        $fields = implode(', ', array_keys($data));

        $placeholders = implode(', ', array_map(fn($c) => ":$c", array_keys($data)));
        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            $this->data[$this->primaryKey] = $this->db->lastInsertId();
        } catch (\Throwable $e) {
            throw new \Exception("Error inserting data: " . $e->getMessage());
        }

        $this->_afterCreate($data);

        return $this;
    }

    /**
     * Hook for custom logic after creating the model.
     *
     * This can be used to perform actions that should occur after the model is created,
     * such as clearing caches or triggering events.
     *
     * @param array $data The data that was used to create the model
     */
    protected function _afterCreate(array $data): void
    {
        // Hook for custom logic after creating the model
    }

    /**
     * Hook for custom logic before creating the model.
     *
     * This can be used to perform actions that should occur before the model is created,
     * such as validating data or setting default values.
     *
     * @param array $data The data that will be used to create the model
     */
    protected function _beforeCreate(array $data): void
    {
        // Hook for custom logic before creating the model
    }

    /**
     * {@inheritDoc}
     */
    public function update(array $data): static
    {
        $this->_beforeUpdate($data);

        $primaryKeyValue = $this->getId();

        if (empty($primaryKeyValue)) {
            throw new \Exception("No primary key value set for update");
        }

        // Remove primary key from data to avoid updating it
        unset($data[$this->primaryKey]);

        // Filter and encode data
        foreach ($data as $key => $value) {
            if ($key === $this->primaryKey) {
                continue;
            }

            if (!in_array($key, $this->describe())) {
                unset($data[$key]);
                continue;
            }
            if (is_array($value)) {
                $data[$key] = json_encode($value);
            }
        }

        if (empty($data)) {
            throw new \Exception("No data to update");
        }

        // Build SET part of SQL
        $fields = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $fields WHERE {$this->primaryKey} = :primary_key";

        // Add primary key to parameters
        $params = $data;
        $params['primary_key'] = $primaryKeyValue;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // Reload data
        $this->load($primaryKeyValue);

        $this->_afterUpdate($data);

        return $this;
    }

    /**
     * Hook for custom logic after updating the model.
     *
     * This can be used to perform actions that should occur after the model is updated,
     * such as clearing caches or triggering events.
     *
     * @param array $data The data that was used to update the model
     */
    protected function _afterUpdate(array $data): void
    {
        // Hook for custom logic after updating the model
    }

    /**
     * Hook for custom logic before updating the model.
     *
     * This can be used to perform actions that should occur before the model is updated,
     * such as validating data or setting default values.
     *
     * @param array $data The data that will be used to update the model
     */
    protected function _beforeUpdate(array $data): void
    {
        // Hook for custom logic before updating the model
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): static
    {
        $this->_beforeDelete($id);

        $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id")
            ->execute(['id' => $id]);
        $this->data = [];
        
        $this->_afterDelete($id);

        return $this;
    }

    /**
     * Hook for custom logic before deleting the model.
     *
     * This can be used to perform actions that should occur before the model is deleted,
     * such as validating data or setting default values.
     *
     * @param int $id The ID of the record to delete
     */
    protected function _beforeDelete(int $id): void
    {
        // Hook for custom logic before deleting the model
    }

    /**
     * Hook for custom logic after deleting the model.
     *
     * This can be used to perform actions that should occur after the model is deleted,
     * such as clearing caches or triggering events.
     *
     * @param int $id The ID of the record that was deleted
     */
    protected function _afterDelete(int $id): void
    {
        // Hook for custom logic after deleting the model
    }


    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
    
}

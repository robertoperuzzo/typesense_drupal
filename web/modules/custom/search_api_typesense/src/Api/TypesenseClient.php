<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Api;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Http\Client\Exception;
use Typesense\Client;
use Typesense\Collection;
use Typesense\Keys;

/**
 * The Search Api Typesense client.
 */
class TypesenseClient implements TypesenseClientInterface {

  use StringTranslationTrait;

  private Client $client;

  /**
   * TypesenseClient constructor.
   *
   * @param \Drupal\search_api_typesense\Api\Config $config
   *   The Typesense config.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   * @throws \Http\Client\Exception
   */
  public function __construct(Config $config) {
    try {
      $this->client = new Client($config->toArray());
      $this->client->health->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function searchDocuments(string $collection_name, array $parameters): array {
    try {
      if ($collection_name != '' || $parameters != '') {
        return [];
      }

      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $this->client->collections[$collection_name]->documents->search($parameters);
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveCollection(?string $collection_name): ?Collection {
    try {
      $collection = $this->client->collections[$collection_name];
      // Ensure that collection exists on the typesense server by retrieving it.
      // This throws exception if it is not found.
      $collection->retrieve();
      return $collection;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection(array $schema): Collection {
    try {
      $this->client->collections->create($schema);
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
    catch (Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }

    return $this->retrieveCollection($schema['name']);
  }

  /**
   * {@inheritdoc}
   */
  public function dropCollection(?string $collection_name): void {
    try {
      $this->client->collections[$collection_name]->delete();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveCollections(): array {
    try {
      return $this->client->collections->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createDocument(string $collection_name, array $document): void {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        $this->client->collections[$collection_name]->documents->upsert($document);
      }

      throw new \Exception($this->t('Error creating document.')->render());
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDocument(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->documents[$id]->retrieve();
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDocument(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->documents[$id]->delete();
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDocuments(string $collection_name, array $filter_condition): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL && count($filter_condition) > 0) {
        return $collection->documents->delete($filter_condition);
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createSynonym(string $collection_name, string $id, array $synonym): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $this->client->collections[$collection_name]->synonyms->upsert($id, $synonym);
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveSynonym(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->synonyms[$id]->retrieve();
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveSynonyms(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->synonyms->retrieve();
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSynonym(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->synonyms[$id]->delete();
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveHealth(): array {
    try {
      return $this->client->health->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDebug(): array {
    try {
      return $this->client->debug->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveMetrics(): array {
    try {
      return $this->client->metrics->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys(): Keys {
    try {
      return $this->client->getKeys();
    }
    catch (SearchApiTypesenseException $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   *   - Figure out int64 -vs- int32 casting.
   *   - Throw an exception if the value received is incompatible with the
   *     declared type.
   *   - Equip this function to handle multiples (i.e. int32[] etc).
   */
  public function prepareItemValue(string|int|array|null $value, string $type): bool|float|int|string {
    if (is_array($value) && count($value) <= 1) {
      $value = reset($value);
    }

    switch ($type) {
      case 'typesense_bool':
        $value = (bool) $value;
        break;

      case 'typesense_float':
        $value = (float) $value;
        break;

      case 'typesense_int32':
      case 'typesense_int64':
        $value = (int) $value;
        break;

      case 'typesense_string':
        $value = (string) $value;
        break;
    }

    return $value;
  }

}

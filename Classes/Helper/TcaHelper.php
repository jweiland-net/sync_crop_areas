<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/sync-crop-areas.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\SyncCropAreas\Helper;

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;

/**
 * Use this helper to get a full merged TCA base configuration for a specific TCA column
 */
class TcaHelper
{
    /**
     * To differ the column configuration and the array key "config" I use "BaseConfiguration" as wording for the
     * column root configuration ($GLOBALS['TCA'][$table]['columns'][$column])
     */
    public function getMergedColumnConfiguration(
        string $table,
        string $column,
        int $pageUid = 0,
        string $type = ''
    ): array {
        $tableConfiguration = $this->getTableConfiguration($table);
        if ($tableConfiguration === []) {
            return [];
        }

        $columnBaseConfiguration = $this->getColumnBaseConfiguration($table, $column);
        if ($columnBaseConfiguration === []) {
            return [];
        }

        $this->mergeWithTypeSpecificConfig(
            $type,
            $column,
            $tableConfiguration,
            $columnBaseConfiguration
        );

        $this->mergeWithPageTsConfig(
            $table,
            $column,
            $type,
            $pageUid,
            $columnBaseConfiguration
        );

        return $columnBaseConfiguration;
    }

    protected function mergeWithPageTsConfig(
        string $table,
        string $column,
        string $type,
        int $pageUid,
        &$columnBaseConfiguration
    ): void {
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageUid);

        // FormEngineUtility::overrideFieldConf checks against "type" which is available within the "config"-part only
        $columnTsConfig = $pageTsConfig['TCEFORM.'][$table . '.'][$column . '.'] ?? [];
        $columnBaseConfiguration['config'] = FormEngineUtility::overrideFieldConf(
            $columnBaseConfiguration['config'],
            $columnTsConfig
        );

        $columnTypeTsConfig = $pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['types.'][$type . '.'] ?? [];
        $columnBaseConfiguration['config'] = FormEngineUtility::overrideFieldConf(
            $columnBaseConfiguration['config'],
            $columnTypeTsConfig
        );
    }

    public function getMergedCropVariants(string $table, string $column, int $pageUid = 0, string $type = ''): array
    {
        return array_replace_recursive(
            $this->getCropVariants(
                'sys_file_reference',
                'crop',
                'config/cropVariants',
                $pageUid
            ),
            $this->getCropVariants(
                $table,
                $column,
                'config/overrideChildTca/columns/crop/config/cropVariants',
                $pageUid,
                $type
            )
        );
    }

    protected function getCropVariants(string $table, string $column, string $path, int $pageUid = 0, string $type = ''): array
    {
        try {
            $cropVariants = (array)ArrayUtility::getValueByPath(
                $this->getMergedColumnConfiguration(
                    $table,
                    $column,
                    $pageUid,
                    $type
                ),
                $path
            );
        } catch (MissingArrayPathException $missingArrayPathException) {
            // Segment of path could not be found in array
            return [];
        } catch (\RuntimeException $runtimeException) {
            // $path is empty
            return [];
        } catch (\InvalidArgumentException $invalidArgumentException) {
            // $path is not string or array
            return [];
        }

        return $cropVariants;
    }

    /**
     * Checks TCA of $table for ctrl->type and uses this value to get entry from $record
     */
    public function getTypeOfRecord(array $record, string $table): string
    {
        $tableConfiguration = $this->getTableConfiguration($table);
        if ($tableConfiguration === []) {
            return '';
        }

        // It also checks, if key "type" is not empty
        if (!$this->tableHasTypeConfiguration($tableConfiguration)) {
            return '';
        }

        $typeColumn = $tableConfiguration['ctrl']['type'];

        if (!array_key_exists($typeColumn, $record)) {
            return '';
        }

        // In case of "pages" column "doktype" can be int. Cast to string
        return $record[$typeColumn] ? (string)$record[$typeColumn] : '';
    }

    /**
     * Returns the column names of a given table which have a relation to sys_file_reference configured in TCA.
     */
    public function getColumnsWithFileReferences(string $table): array
    {
        $columnsWithFileReferences = [];
        foreach ($this->getTcaConfiguredColumnNamesOfTable($table) as $column) {
            try {
                $foreignTable = (string)ArrayUtility::getValueByPath(
                    $GLOBALS,
                    sprintf(
                        'TCA/%s/columns/%s/config/foreign_table',
                        $table,
                        $column
                    )
                );
            } catch (MissingArrayPathException $missingArrayPathException) {
                // Segment of path could not be found in array
                continue;
            } catch (\RuntimeException $runtimeException) {
                // $path is empty
                continue;
            } catch (\InvalidArgumentException $invalidArgumentException) {
                // $path is not string or array
                continue;
            }

            if ($foreignTable === '') {
                continue;
            }

            if ($foreignTable !== 'sys_file_reference') {
                continue;
            }

            $columnsWithFileReferences[] = $column;
        }

        return $columnsWithFileReferences;
    }

    protected function getTcaConfiguredColumnNamesOfTable(string $table): array
    {
        $tableConfiguration = $this->getTableConfiguration($table);
        if ($tableConfiguration === []) {
            return [];
        }

        if (!array_key_exists('columns', $tableConfiguration)) {
            return [];
        }

        return is_array($tableConfiguration['columns']) ? array_keys($tableConfiguration['columns']) : [];
    }

    /**
     * Merge TCA config of a column with a specific TCA type configuration
     *
     * @param string $type The type like "textmedia", "tables", "images", ...
     * @param string $column The column of the type specific table configuration
     * @param array $tableConfiguration The TCA config of $GLOBALS['TCA'][$table]
     * @param array $columnBaseConfiguration The TCA config of $GLOBALS['TCA'][$table]['columns'][$column]
     */
    protected function mergeWithTypeSpecificConfig(
        string $type,
        string $column,
        array $tableConfiguration,
        array &$columnBaseConfiguration
    ): void {
        if ($type === '') {
            return;
        }

        if (!$this->tableHasTypeConfiguration($tableConfiguration)) {
            return;
        }

        $typeSpecificBaseConfigurationForColumn = $this->getTableTypeBaseConfigurationForColumn(
            $column,
            $type,
            $tableConfiguration
        );

        if ($typeSpecificBaseConfigurationForColumn === []) {
            return;
        }

        if (!array_key_exists('config', $typeSpecificBaseConfigurationForColumn)) {
            return;
        }

        ArrayUtility::mergeRecursiveWithOverrule(
            $columnBaseConfiguration,
            $typeSpecificBaseConfigurationForColumn,
            true,
            true,
            false
        );
    }

    protected function tableHasTypeConfiguration(array $tableConfiguration): bool
    {
        if (!array_key_exists('ctrl', $tableConfiguration)) {
            return false;
        }

        if (!is_array($tableConfiguration['ctrl'])) {
            return false;
        }

        if (!array_key_exists('type', $tableConfiguration['ctrl'])) {
            return false;
        }

        return !empty($tableConfiguration['ctrl']['type']);
    }

    protected function getColumnBaseConfiguration(string $table, string $column): array
    {
        $tableConfiguration = $this->getTableConfiguration($table);

        if (!array_key_exists('columns', $tableConfiguration)) {
            return [];
        }

        if (!array_key_exists($column, $tableConfiguration['columns'])) {
            return [];
        }

        return is_array($tableConfiguration['columns'][$column]) ? $tableConfiguration['columns'][$column] : [];
    }

    /**
     * If the BaseConfiguration is a relation to another table, it is possible to
     *
     * @ToDo: Make public. Implement something to merge config of sys_file_references
     *
     * @param string $column
     * @param string $type
     * @param array $tableConfiguration
     * @return array
     */
    protected function getTableTypeBaseConfigurationForCrop(
        string $column,
        string $type,
        array $tableConfiguration
    ): array {
        $baseConfiguration = $this->getTableTypeBaseConfigurationForColumn($column, $type, $tableConfiguration);
        return [];
    }

    /**
     * Returns just the type-individual BaseConfiguration for table and column.
     * $GLOBALS['TCA'][$table]['types'][$type]['columnsOverrides'][$column]
     */
    protected function getTableTypeBaseConfigurationForColumn(
        string $column,
        string $type,
        array $tableConfiguration
    ): array {
        $tableTypeConfiguration = $this->getTableTypeConfigurationForType($type, $tableConfiguration);
        if ($tableTypeConfiguration === []) {
            return [];
        }

        if (!array_key_exists('columnsOverrides', $tableTypeConfiguration)) {
            return [];
        }

        if (!array_key_exists($column, $tableTypeConfiguration['columnsOverrides'])) {
            return [];
        }

        return is_array($tableTypeConfiguration['columnsOverrides'][$column])
            ? $tableTypeConfiguration['columnsOverrides'][$column]
            : [];
    }

    protected function getTableTypeConfigurationForType(string $type, array $tableConfiguration): array
    {
        $tableTypesConfiguration = $this->getTableTypesConfiguration($tableConfiguration);
        if (!array_key_exists($type, $tableTypesConfiguration)) {
            return [];
        }

        return is_array($tableTypesConfiguration[$type]) ? $tableTypesConfiguration[$type] : [];
    }

    protected function getTableTypesConfiguration(array $tableConfiguration): array
    {
        if (!array_key_exists('types', $tableConfiguration)) {
            return [];
        }

        return is_array($tableConfiguration['types']) ? $tableConfiguration['types'] : [];
    }

    protected function getTableConfiguration(string $table): array
    {
        if (!array_key_exists('TCA', $GLOBALS)) {
            return [];
        }

        if (!array_key_exists($table, $GLOBALS['TCA'])) {
            return [];
        }

        return is_array($GLOBALS['TCA'][$table]) ? $GLOBALS['TCA'][$table] : [];
    }
}

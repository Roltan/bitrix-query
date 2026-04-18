<?php

declare(strict_types=1);

namespace Api\Requests;

class FileRequest
{
//    private array $allowedTypes = [
//        'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
//        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
//    ];

    private int $maxFileSize = 5 * 1024 * 1024; // 5MB

    private array $uploadedFiles = [];

    /**
     * Валидация загруженных файлов
     */
    public function validateFiles(): bool
    {
        if (empty($_FILES)) {
            return true; // Нет файлов - валидация успешна
        }

        $normalizedFiles = $this->normalizeFilesArray($_FILES);

        foreach ($normalizedFiles as $file) {
            // Проверяем ошибки загрузки
            if ($file['error'] !== UPLOAD_ERR_OK) {
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                http_response_code(422);
                throw new \Error("Ошибка загрузки файла: {$file['name']}", 422);
            }

            // Валидация размера файла
//            if ($file['size'] > $this->maxFileSize) {
//                http_response_code(422);
//                throw new \Error("Файл {$file['name']} превышает максимальный размер 5MB", 422);
//            }

            // Валидация типа файла
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

//            if (!in_array($mimeType, $this->allowedTypes)) {
//                http_response_code(422);
//                throw new \Error("Недопустимый тип файла: {$file['name']}", 422);
//            }

            // Сохраняем временные данные файла для последующего сохранения
            $this->uploadedFiles[] = [
                'tmp_name' => $file['tmp_name'],
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => $file['size'],
                'mime_type' => $mimeType
            ];
        }

        return true; // Валидация прошла успешно
    }

    /**
     * Сохранение валидных файлов и возврат путей
     */
    public function saveFiles(): ?array
    {
        if (empty($this->uploadedFiles)) {
            return null;
        }

        $savedFiles = [];

        foreach ($this->uploadedFiles as $file) {
            $fileId = $this->saveFileToBitrix($file);

            if ($fileId) {
                $savedFiles[] = [
                    'file_id' => $fileId,
                    'original_name' => $file['name'],
                    'size' => $file['size'],
                    'mime_type' => $file['mime_type'],
                    'path' => $this->getFilePathById($fileId) // Получаем путь по ID файла
                ];
            }
        }

        // Очищаем временные данные
        $this->uploadedFiles = [];

        return !empty($savedFiles) ? $savedFiles : null;
    }

    /**
     * Получение пути файла по его ID в Битрикс
     */
    private function getFilePathById(int $fileId): ?string
    {
        $fileInfo = \CFile::GetFileArray($fileId);
        return $fileInfo ? $fileInfo['SRC'] : null;
    }

    /**
     * Сохранение файла в системе Битрикс
     */
    private function saveFileToBitrix(array $file): ?int
    {
        $fileArray = \CFile::MakeFileArray($file['tmp_name']);
        $fileArray['name'] = $file['name'];
        $fileArray['type'] = $file['type'];

        if ($fileArray && !isset($fileArray['error'])) {
            $fileId = \CFile::SaveFile($fileArray, 'iblock');
            return $fileId ?: null;
        }

        return null;
    }

    /**
     * Нормализует структуру $_FILES для удобной обработки
     */
    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];

        foreach ($files as $fieldName => $fileData) {
            // Если это массив файлов (multiple upload)
            if (is_array($fileData['name'])) {
                $fileCount = count($fileData['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($fileData['error'][$i] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $normalized[] = [
                        'name' => $fileData['name'][$i],
                        'type' => $fileData['type'][$i],
                        'tmp_name' => $fileData['tmp_name'][$i],
                        'error' => $fileData['error'][$i],
                        'size' => $fileData['size'][$i]
                    ];
                }
            } else {
                // Одиночный файл
                if ($fileData['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $normalized[] = $fileData;
            }
        }

        return $normalized;
    }
}
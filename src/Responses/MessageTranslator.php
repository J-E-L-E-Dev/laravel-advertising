<?php

namespace Ahinest\LaravelAdvertising\Responses;

/** Traduce mensajes del paquete usando las traducciones JSON del proyecto Laravel. */
class MessageTranslator
{
    /**
     * Convierte mensajes internos históricos a claves inglesas y las traduce.
     * Si el idioma activo no define la clave, Laravel devuelve el texto inglés.
     */
    public static function translate(string $message): string
    {
        $key = self::KEYS[$message] ?? $message;
        return __($key);
    }

    private const KEYS = [
        'Categoría creada.' => 'Category created.',
        'Categoría actualizada.' => 'Category updated.',
        'Categorías encontradas.' => 'Categories found.',
        'Categoría encontrada.' => 'Category found.',
        'Categoría en papelera encontrada.' => 'Trashed category found.',
        'Categoría no encontrada.' => 'Category not found.',
        'Categoría en papelera no encontrada.' => 'Trashed category not found.',
        'Recurso creado.' => 'Resource created.',
        'Recurso actualizado.' => 'Resource updated.',
        'Recursos encontrados.' => 'Resources found.',
        'Recurso encontrado.' => 'Resource found.',
        'Recurso no encontrado.' => 'Resource not found.',
        'Recursos en papelera encontrados.' => 'Trashed resources found.',
        'Recurso en papelera encontrado.' => 'Trashed resource found.',
        'Recurso en papelera no encontrado.' => 'Trashed resource not found.',
        'Contenedor creado.' => 'Container created.',
        'Contenedor actualizado.' => 'Container updated.',
        'Contenedores encontrados.' => 'Containers found.',
        'Contenedor encontrado.' => 'Container found.',
        'Contenedor no encontrado.' => 'Container not found.',
        'Contenedores en papelera encontrados.' => 'Trashed containers found.',
        'Contenedor en papelera encontrado.' => 'Trashed container found.',
        'Contenedor en papelera no encontrado.' => 'Trashed container not found.',
        'Anuncio creado.' => 'Advertisement created.',
        'Anuncio actualizado.' => 'Advertisement updated.',
        'Anuncios encontrados.' => 'Advertisements found.',
        'Anuncio encontrado.' => 'Advertisement found.',
        'Anuncio no encontrado.' => 'Advertisement not found.',
        'Anuncios en papelera encontrados.' => 'Trashed advertisements found.',
        'Anuncio en papelera encontrado.' => 'Trashed advertisement found.',
        'Anuncio en papelera no encontrado.' => 'Trashed advertisement not found.',
        'Registro eliminado definitivamente.' => 'Record permanently deleted.',
        'Registro enviado a la papelera.' => 'Record moved to trash.',
        'Registro restaurado.' => 'Record restored.',
        'Registros restaurados.' => 'Records restored.',
        'No se encontraron registros para restaurar.' => 'No records were found to restore.',
        'Registro encontrado.' => 'Record found.',
        'Registros encontrados.' => 'Records found.',
        'Registros eliminados encontrados.' => 'Trashed records found.',
        'Registro no encontrado.' => 'Record not found.',
        'Registro no encontrado en papelera.' => 'Trashed record not found.',
        'Solicitud incorrecta o incompleta; revise los campos permitidos.' => 'The request is incorrect or incomplete; review the allowed fields.',
        'Ocurrió un error interno; revise el registro de Laravel.' => 'An internal error occurred; review the Laravel log.',
        'No fue posible consultar los registros.' => 'Unable to retrieve records.',
        'No fue posible consultar los recursos.' => 'Unable to retrieve resources.',
        'No fue posible consultar los registros en papelera.' => 'Unable to retrieve trashed records.',
        'No fue posible consultar el registro.' => 'Unable to retrieve the record.',
        'Se detectó un archivo image o video, pero el recurso no tiene una categoría válida. Envíe category o category_id y revise la documentación.' => 'An image or video file was detected, but the resource has no valid category. Send category or category_id and review the documentation.',
        'La categoría del recurso requiere un video con una extensión permitida.' => 'The resource category requires a video with an allowed extension.',
        'La categoría del recurso requiere una imagen válida.' => 'The resource category requires a valid image.',
        'No fue posible almacenar el archivo publicitario.' => 'Unable to store the advertising file.',
    ];
}

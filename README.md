# UndieCrush - Proyecto de WordPress

Este repositorio contiene el código fuente del proyecto de WordPress `undiecrush`.

## ⚠️ AVISO IMPORTANTE SOBRE LA RESTAURACIÓN ⚠️

Este repositorio **NO contiene las imágenes, videos ni archivos multimedia** del sitio (la carpeta `wp-content/uploads/` está ignorada por límites de espacio de GitHub). 

Para restaurar este proyecto en un nuevo servidor, sigue estos pasos:

### 1. Clonar el repositorio
```bash
git clone https://github.com/rowgon/undiecrush.git
cd undiecrush
```

### 2. Restaurar la Base de Datos
En la raíz del proyecto encontrarás el archivo de base de datos comprimido `database_backup.sql.gz`.
Para importarlo en tu nuevo servidor de base de datos:
```bash
# Descomprimir
gunzip database_backup.sql.gz
# Importar (ajusta el nombre de usuario y base de datos)
mysql -u TU_USUARIO -p TU_BASE_DE_DATOS < database_backup.sql
```

### 3. Restaurar las Imágenes (`uploads/`)
Debes copiar manualmente la carpeta `uploads/` que hayas respaldado en tu disco externo o Google Drive, y pegarla en:
`wp-content/uploads/`

### 4. Configurar el acceso a la Base de Datos
Edita el archivo `wp-config.php` para asegurarte de que las credenciales coincidan con las de tu nuevo servidor local o de producción.

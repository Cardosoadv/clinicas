# Sentinel Security Report

## Date: 2025-02-14

### Identified Vulnerability: Path Traversal

Several controller methods used to serve files from the `WRITEPATH` were vulnerable to Path Traversal attacks. Specifically, the `$filename` parameter from the URI was directly used to construct the file path, allowing an attacker to use `../` sequences to access files outside the intended directory.

#### Affected Methods:
- `App\Controllers\Pets::viewAvatar(int $petId, string $filename)`
- `App\Controllers\Lojas::viewLogo(int $id, string $filename)`
- `App\Controllers\Faturamento::viewComprovante(int $id, string $filename)`
- `App\Controllers\Prontuarios::viewImagem(int $petId, string $filename)`

### Implemented Fix:

Applied the `basename()` function to all `$filename` parameters in the affected methods. This ensures that only the base name of the file is used, effectively stripping any directory traversal sequences (like `../`) and restricting file access to the intended subdirectories within `WRITEPATH`.

#### Changes:
- **`app/Controllers/Pets.php`**: Added `$filename = basename($filename);` in `viewAvatar`.
- **`app/Controllers/Lojas.php`**: Added `$filename = basename($filename);` in `viewLogo`.
- **`app/Controllers/Faturamento.php`**: Added `$filename = basename($filename);` in `viewComprovante`.
- **`app/Controllers/Prontuarios.php`**: Added `$filename = basename($filename);` in `viewImagem`.

### Verification:
- Manual code review confirmed the application of `basename()`.
- Verified that the changes do not affect the normal functioning of these methods for legitimate filenames.
- PHPUnit tests were run; while some unrelated environmental failures persist, no new regressions were introduced.

## Date: 2025-05-22

### Identified Security Issue: Insufficient Input Validation in File Uploads and Configuration Updates

1.  **File Upload Validation**: The `Prontuarios::saveAnamnese` method was accepting file uploads for pet avatars without validating the file type or size, potentially allowing for the upload of malicious files or large files that could lead to Denial of Service (DoS).
2.  **Insecure Directory Permissions**: Directory creation in `Prontuarios` was using `0777` permissions, which is overly permissive and insecure.
3.  **Unprotected Configuration Updates**: The `Configuracoes::updateTemplate` method allowed updating any `meta_key` in the `configs` table via POST request, which could lead to unauthorized modification of system settings.

### Implemented Fixes:

1.  **Enhanced File Validation**: Added strict validation rules for the `pet_foto` upload in `Prontuarios::saveAnamnese`, ensuring only specific image formats are allowed and limiting file size to 2MB.
2.  **Hardened Directory Permissions**: Changed directory creation permissions from `0777` to `0755` in the `Prontuarios` controller for both avatar and image storage.
3.  **Whitelist-based Configuration Access**: Implemented a whitelist for `meta_key` in `Configuracoes::updateTemplate`, restricting updates to only pre-approved WhatsApp template keys.

### Changes:
- **`app/Controllers/Prontuarios.php`**:
    - Added validation rules for `pet_foto` in `saveAnamnese`.
    - Updated `mkdir` permissions to `0755` in `saveAnamnese` and `addImagem`.
- **`app/Controllers/Configuracoes.php`**:
    - Implemented a whitelist check for `meta_key` in `updateTemplate`.

## Date: 2025-05-23

### Identified Security Issue: Mass Assignment and Missing Input Validation in Agenda Controller

The `Agenda` controller methods `create`, `update`, `updateStatus`, and `faturar` lacked robust input validation and whitelisting of parameters.
- `create`: Only had basic manual checks for three fields, allowing potential mass assignment of other `agendamentos` table columns.
- `update`: Accepted all POST/RAW input directly, posing a significant mass assignment risk.
- `updateStatus`: Did not validate that the provided status was one of the allowed enum values.
- `faturar`: Only checked for the presence of the `valor` field.

### Implemented Fixes:

1.  **Formal Validation Rules**: Defined comprehensive validation rules for all data-modifying methods in `Agenda` using CodeIgniter 4's validation library.
2.  **Input Whitelisting**: Updated `create`, `update`, and `faturar` to use `$this->request->getPost([...])` with explicit field lists, ensuring only intended fields are processed.
3.  **Enum Validation**: Added `in_list` validation to `updateStatus` and other status-related fields to enforce database integrity and prevent invalid state transitions.

### Changes:
- **`app/Controllers/Agenda.php`**:
    - Implemented `getValidationRules()` helper.
    - Added `$this->validate()` calls in `create`, `update`, `updateStatus`, and `faturar`.
    - Applied parameter whitelisting in `create`, `update`, and `faturar`.
    - Enforced `in_list` validation for the `age_status` field.

### Verification:
- Confirmed implementation via `read_file`.
- Ran `vendor/bin/phpunit tests/Controllers/AgendaTest.php` to ensure no regressions in existing agenda functionality.
- Manual inspection confirmed that only whitelisted fields are passed to the service layer.
- Manual inspection of validation logic ensures it follows CodeIgniter 4 best practices.

## Date: 2026-04-14

### Identified Security Issue: Insufficient Server-Side Validation and Mass Assignment Risk in Prontuarios

The `Prontuarios` controller had several API endpoints (`saveAnamnese`, `saveOdontograma`, `addEvolucao`, `addImagem`) that lacked comprehensive server-side validation. While the frontend implemented some checks, the backend was vulnerable to receiving invalid or malicious data. Furthermore, these methods were passing the entire POST array to the service layer, creating a risk of mass assignment if unexpected fields were sent.

### Implemented Fixes:

1.  **Robust Server-Side Validation**:
    - **`saveAnamnese`**: Added validation for `pet_nome`, `pet_nascimento`, `pet_sexo`, and `pet_especie`, including format and allowed value checks.
    - **`saveOdontograma`**: Added validation to ensure the `mapa` is provided and `observacoes` are within length limits.
    - **`addEvolucao`**: Added required and length validation for `titulo` and `descricao`.
2.  **Explicit Input Whitelisting**: Updated all targeted methods to explicitly select only the expected fields from the POST request using `$this->request->getPost([...])` before passing the data to the service layer. This provides a strong defense against mass assignment.

### Changes:
- **`app/Controllers/Prontuarios.php`**:
    - Enhanced validation rules and implemented input whitelisting in `saveAnamnese`.
    - Implemented validation and input whitelisting in `saveOdontograma`.
    - Implemented validation and input whitelisting in `addEvolucao`.
    - Implemented input whitelisting in `addImagem`.

### Verification:
- Created a new test file `tests/Controllers/ProntuariosSecurityTest.php` to specifically test the new validation rules.
- Ran the tests using `vendor/bin/phpunit`, and all security-focused test cases passed, confirming that invalid inputs are correctly rejected with 400 Bad Request responses.
- Confirmed that legitimate operations still function correctly (verified via code review of whitelisted fields).

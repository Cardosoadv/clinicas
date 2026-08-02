# Davinci Discovery Report 🎨

## Analysis Summary - 2025-05-14

### Domain: Veterinary Management System (Petys)
The system currently handles:
- **Agendamentos:** Appointment booking and status management.
- **Faturamento:** Billing, packages, and expenses.
- **Prontuários:** Clinical records, including anamnese, odontograms, and imaging.
- **Estoque:** Basic product management and movement.
- **Comunicação:** WhatsApp link generation for appointments and billing.

### Identified Feature Seeds

#### 1. Communication Expansion
Current `ComunicacaoService` is limited to appointment reminders and billing alerts.
*   **Seed:** The data exists in `agendamentos` and `fat_cobrancas` to trigger other types of communication.
*   **Action:** Implementing "Pós-consulta" (Post-care) messaging to close the care loop.

#### 2. Inventory Intelligence
`EstoqueProdutosModel` contains metadata like `estoque_minimo` which is currently underutilized.
*   **Seed:** Products have min/max stock levels.
*   **Opportunity:** Automated replenishment alerts and integration with the service billing flow.

#### 3. Clinical History & CRM
`PetsModel` contains demographic data (`pet_nascimento`) and `EvolucoesModel` contains clinical data.
*   **Seed:** Birthdays and procedure history.
*   **Opportunity:** Personalized marketing and automated medical follow-ups.

### Implemented Improvements
- Added `getWhatsAppLinkPosConsulta` to `ComunicacaoService` to support post-care follow-up.
- Exposed new API endpoint for post-care messaging.
- **Birthday CRM:** Implemented birthday detection in `PetsRepository` and `PetsService`.
- **Engagement Automation:** Added `getWhatsAppLinkAniversario` to `ComunicacaoService` and exposed it via API.
- **Dashboard Intelligence:** Integrated monthly birthdays and low stock alerts into the main Dashboard view for immediate action.
- **Vaccination Tracking System:** Implemented a structured vaccination module including the 'vacinas' table, VacinaModel, and VacinasRepository (findByPet, getUpcomingExpirations). UI features a dedicated 'Vacinas' tab in the Medical Record (Prontuários).
- **Vaccination CRM:** Automated WhatsApp reminders for upcoming/overdue vaccines integrated into both the medical record and the main dashboard.
- **Post-Care Follow-up Expansion:** Identified that 'concluido' appointments are the primary trigger for post-care communication. Expanded the dashboard to surfacing candidates for follow-up messages.
- **Weight & Growth Tracking:** Implemented a full module for tracking pet weight over time. This includes database migration, WeightModel, PesosRepository, and a visual SVG chart in the Medical Record. It enables better nutritional management and clinical follow-up.
- **Veterinary Dosage Calculator:** Integrated a calculation tool directly into the medical record evolution tab. Automatically utilizes the pet's latest weight to suggest dosages, reducing human error.
- **Clinical Evolution Templates:** Added standard templates for common procedures (Consulta, Vacina, Retorno, Cirurgia) to improve documentation speed and quality.

### Deep Dive: Weight-to-Medication Link
The newly implemented weight tracking provides a deterministic foundation for medical safety.
*   **Insight:** Weight is the most important variable for medication safety in small animals. Having a history allows detecting "silent" weight loss, which is often the first sign of chronic disease in cats and senior dogs.
*   **Action:** Implementing a "Veterinary Dosage Calculator" that leverages this data.

### Deep Dive: Clinical Metadata
The current `pet_condicoes` field stores JSON strings like "Vacinação OK".
*   **Insight:** This indicates a clear need for a structured Vaccine/Deworming module. Using simple tags is prone to error and doesn't allow for automated reminders.
*   **Action:** Mapped this as a high-priority feature in `todo.md`.

### Deep Dive: Post-Care Loop
The system has a `ComunicacaoService::getWhatsAppLinkPosConsulta` but it was underutilized because users had to navigate to specific records to find candidates.
*   **Insight:** Bringing "Post-care candidates" (pets seen in the last 48h) to the dashboard drastically increases the likelihood of use.

## Analysis Summary - 2025-05-16

### Clinical Tooling: Dosage Calculator
Prescribing medications is a high-frequency, high-risk task for veterinarians.
*   **Insight:** Every clinical evolution requires dosage calculations. Automating this within the medical record reduces cognitive load and errors.
*   **Action:** Implementing a Veterinary Dosage Calculator directly in the 'Evolução' tab.

### Future Clinical Capabilities
Analysis of `ProntuariosService` and `EvolucoesModel` shows a generic text-based system.
*   **Seed:** "Evoluções" are simple text fields.
*   **Opportunity:** Creating structured document generators (Prescriptions, Certificates) and integrating laboratory data tracking.

## Analysis Summary - 2025-05-22

### Clinical Quality: Structured Prescriptions
The "Prescrição" tab in `Prontuários` is currently a placeholder.
*   **Insight:** Prescriptions are legal documents. Having them as structured data (Medication, Dose, Frequency, Duration) instead of plain text in an evolution allows for:
    1.  Professional PDF generation.
    2.  Medication history tracking.
    3.  Automated reminders for tutor (via WhatsApp).
*   **Action:** Implementing a Structured Prescription Module.

## Analysis Summary - 2025-05-20

### Inventory Automation: Service-Inventory Link (BOM)
Analysis of `ServicosService`, `EstoqueService`, and `FatService` reveals a gap between clinical procedures and inventory consumption.
*   **Insight:** Many veterinary services (e.g., vaccines, surgeries) consume specific products. Currently, veterinarians or staff must manually record stock exits after billing.
*   **Opportunity:** Creating a "Bill of Materials" (BOM) for services. By linking products to services, the system can automatically deduct stock when a service is billed and paid.
*   **Decision:** Implement the `servico_produtos` junction table and integrate stock deduction into the `FatService` billing flow.

# ERD (generováno, heuristika ze source kódu)

```mermaid
erDiagram
  KNOWLEDGECHUNK }o--|| KNOWLEDGEDOCUMENT : belongs_to
  LEAD }o--|| USER : belongs_to
  LEAD }o--|| OPPORTUNITY : belongs_to
  LEAD ||--o{ TASK : morph_many
  TASK }o--|| USER : belongs_to
  TASK }o--|| PROJECT : belongs_to
  TASK }o..o{ POLYMORPHIC : morph_to
  SALESORDER }o--|| COMPANY : belongs_to
  SALESORDER }o--|| CONTACT : belongs_to
  SALESORDER ||--o{ SALESORDERITEM : has_many
  PRODUCT ||--o{ PRODUCTPRICECHANGE : has_many
  PRODUCT ||--o{ PRODUCTAVAILABILITYCHANGE : has_many
  OPPORTUNITY }o--|| CONTACT : belongs_to
  OPPORTUNITY }o--|| COMPANY : belongs_to
  OPPORTUNITY }o--|| USER : belongs_to
  COMPANY }o--|| USER : belongs_to
  COMPANY ||--o{ CONTACT : has_many
  COMPANY ||--o{ OPPORTUNITY : has_many
  COMPANY ||--o{ PROJECT : has_many
  COMPANY ||--o{ SALESORDER : has_many
  COMPANY ||--o{ TASK : morph_many
  CONTACT }o--|| COMPANY : belongs_to
  CONTACT }o--|| USER : belongs_to
  CONTACT ||--o{ OPPORTUNITY : has_many
  CONTACT ||--o{ PROJECT : has_many
  CONTACT ||--o{ SALESORDER : has_many
  CONTACT ||--o{ CONTACTCUSTOMFIELD : has_many
  CONTACT ||--o{ CONTACTIDENTITY : has_many
  CONTACT ||--o{ TASK : morph_many
  PRODUCTGROUP ||--o{ PRODUCTGROUP : has_many
  CONTACTCUSTOMFIELD }o--|| CONTACT : belongs_to
  PRODUCTPRICECHANGE }o--|| PRODUCT : belongs_to
  PROJECT }o--|| COMPANY : belongs_to
  PROJECT }o--|| CONTACT : belongs_to
  PROJECT }o--|| USER : belongs_to
  PROJECT ||--o{ TASK : has_many
  DEAL }o--|| OPPORTUNITY : belongs_to
  DEAL }o--|| CONTACT : belongs_to
  DEAL }o--|| USER : belongs_to
  PRODUCTAVAILABILITYCHANGE }o--|| PRODUCT : belongs_to
  CONTACTIDENTITY }o--|| CONTACT : belongs_to
  KNOWLEDGENOTE }o--|| USER : belongs_to
  KNOWLEDGEDOCUMENT }o--|| USER : belongs_to
  KNOWLEDGEDOCUMENT ||--o{ KNOWLEDGECHUNK : has_many
  SALESORDERITEM }o--|| SALESORDER : belongs_to
  KNOWLEDGECHUNK {
    string document_id
    string chunk_index
    string text
    array meta
    array embedding
    string embedding_dim
    datetime embedded_at
    string qdrant_point_id
    string chunk_hash
  }
  LEAD {
    string company_name
    string contact_name
    string email
    string phone
    string source
    string status
    integer score
    decimal:2 estimated_value
    string notes
    string assigned_to
    string created_by
    datetime last_activity_at
    datetime converted_at
    string converted_to_opportunity_id
  }
  TASK {
    string title
    string description
    string type
    string status
    string priority
    string project_id
    datetime due_date
    datetime completed_at
    string assigned_to
    string created_by
    string taskable_type
    string taskable_id
    string notes
  }
  SALESORDER {
    string external_order_no
    string company_id
    string contact_id
    date order_date
    string author
    decimal:2 total_amount
    string source
    string notes
  }
  PRODUCT {
    string external_id
    string group_id
    string name
    string description
    string price_vat_cents
    string currency
    string manufacturer
    string ean
    string category_path
    string category_hash
    string url
    string image_url
    string availability_code
    string availability_text
    string stock_quantity
    datetime availability_synced_at
    string hash_payload
    datetime first_imported_at
    datetime last_synced_at
    datetime last_price_changed_at
    datetime last_availability_changed_at
  }
  OPPORTUNITY {
    string name
    string description
    decimal:2 value
    integer probability
    string stage
    date expected_close_date
    string contact_id
    string company_id
    string assigned_to
    string created_by
    datetime closed_at
    string close_reason
    string close_notes
  }
  COMPANY {
    string name
    string industry
    string size
    string status
    string website
    string phone
    string email
    string address
    string city
    string country
    string notes
    decimal:2 annual_revenue
    string employee_count
    string created_by
  }
  ACSYNCRUN {
    datetime started_at
    datetime finished_at
    string limit
    string offset
    string created
    string updated
    string skipped
    string skipped_unchanged
    string errors
    array sample_created_ids
    array sample_updated_ids
    string message
  }
  CONTACT {
    string company_id
    string first_name
    string last_name
    string email
    string normalized_email
    string phone
    string normalized_phone
    string mobile
    string position
    string department
    string status
    string marketing_status
    date birthday
    string address
    string city
    string country
    string notes
    array social_links
    string preferred_contact
    string created_by
    datetime last_contacted_at
    string ac_id
    string ac_hash
    datetime ac_updated_at
    string legacy_external_id
  }
  SYSTEMSETTING {
    string key
    string value
  }
  PRODUCTGROUP {
    string code
    string name
    string name_alt
    string eshop_url
    string parent_id
  }
  APPLINK {
    string name
    string url
    string icon_url
    integer position
    boolean is_active
  }
  USER {
    string name
    string email
    string password
    string is_admin
  }
  CONTACTCUSTOMFIELD {
    string contact_id
    string key
    string value
    string type
    string ac_field_id
  }
  OPSACTIVITY {
    string type
    string status
    string user_id
    datetime started_at
    datetime finished_at
    string duration_ms
    array meta
    string log_excerpt
  }
  CHATTOOLAUDIT {
    string user_id
    string conversation_id
    string tool
    string intent
    array payload
    array result_meta
    string duration_ms
  }
  TAG {
    string name
    string source
  }
  PRODUCTPRICECHANGE {
    string product_id
    string old_price_cents
    string new_price_cents
    datetime changed_at
  }
  PROJECT {
    string name
    string description
    string status
    date start_date
    date due_date
    string company_id
    string contact_id
    string assigned_to
    string created_by
  }
  DEAL {
    string opportunity_id
    string name
    decimal:2 amount
    date close_date
    string status
    string terms
    string notes
    string signed_by_contact_id
    datetime signed_at
    string created_by
  }
  PRODUCTAVAILABILITYCHANGE {
    string product_id
    string old_code
    string new_code
    string old_stock_qty
    string new_stock_qty
    datetime changed_at
  }
  CONTACTIDENTITY {
    string contact_id
    string source
    string external_id
    string external_hash
  }
  KNOWLEDGENOTE {
    string user_id
    string title
    string content
    array tags
    string visibility
  }
  KNOWLEDGEDOCUMENT {
    string user_id
    string title
    string source_type
    string mime
    string size
    string path
    string status
    string visibility
    array tags
    string error
    datetime vectorized_at
    string embedding_provider
    string embedding_model
    string embedding_dim
    string vectors_count
    string last_index_duration_ms
  }
  SALESORDERITEM {
    string sales_order_id
    string sku
    string alt_code
    string name
    string name_alt
    decimal:3 qty
    decimal:4 unit_price
    decimal:4 unit_price_disc
    decimal:4 cost
    decimal:4 cost_disc
    decimal:2 discounts_card
    decimal:2 discounts_group
    string product_group
    string eshop_category_url
    string tax_code
    string currency
  }
```

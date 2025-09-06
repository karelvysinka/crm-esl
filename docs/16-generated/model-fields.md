# Model Fields (generováno)

_Heuristický výpis na základě $fillable a $casts. Skutečné DB typy ověřte migracemi._

## KnowledgeChunk

| Pole | Typ (cast) |
|------|-----------|
| document_id |  |
| chunk_index |  |
| text |  |
| meta | array |
| embedding | array |
| embedding_dim |  |
| embedded_at | datetime |
| qdrant_point_id |  |
| chunk_hash |  |

## Lead

| Pole | Typ (cast) |
|------|-----------|
| company_name |  |
| contact_name |  |
| email |  |
| phone |  |
| source |  |
| status |  |
| score | integer |
| estimated_value | decimal:2 |
| notes |  |
| assigned_to |  |
| created_by |  |
| last_activity_at | datetime |
| converted_at | datetime |
| converted_to_opportunity_id |  |

## Task

| Pole | Typ (cast) |
|------|-----------|
| title |  |
| description |  |
| type |  |
| status |  |
| priority |  |
| project_id |  |
| due_date | datetime |
| completed_at | datetime |
| assigned_to |  |
| created_by |  |
| taskable_type |  |
| taskable_id |  |
| notes |  |

## SalesOrder

| Pole | Typ (cast) |
|------|-----------|
| external_order_no |  |
| company_id |  |
| contact_id |  |
| order_date | date |
| author |  |
| total_amount | decimal:2 |
| source |  |
| notes |  |

## Product

| Pole | Typ (cast) |
|------|-----------|
| external_id |  |
| group_id |  |
| name |  |
| description |  |
| price_vat_cents |  |
| currency |  |
| manufacturer |  |
| ean |  |
| category_path |  |
| category_hash |  |
| url |  |
| image_url |  |
| availability_code |  |
| availability_text |  |
| stock_quantity |  |
| availability_synced_at | datetime |
| hash_payload |  |
| first_imported_at | datetime |
| last_synced_at | datetime |
| last_price_changed_at | datetime |
| last_availability_changed_at | datetime |

## Opportunity

| Pole | Typ (cast) |
|------|-----------|
| name |  |
| description |  |
| value | decimal:2 |
| probability | integer |
| stage |  |
| expected_close_date | date |
| contact_id |  |
| company_id |  |
| assigned_to |  |
| created_by |  |
| closed_at | datetime |
| close_reason |  |
| close_notes |  |

## Company

| Pole | Typ (cast) |
|------|-----------|
| name |  |
| industry |  |
| size |  |
| status |  |
| website |  |
| phone |  |
| email |  |
| address |  |
| city |  |
| country |  |
| notes |  |
| annual_revenue | decimal:2 |
| employee_count |  |
| created_by |  |

## AcSyncRun

| Pole | Typ (cast) |
|------|-----------|
| started_at | datetime |
| finished_at | datetime |
| limit |  |
| offset |  |
| created |  |
| updated |  |
| skipped |  |
| skipped_unchanged |  |
| errors |  |
| sample_created_ids | array |
| sample_updated_ids | array |
| message |  |

## Contact

| Pole | Typ (cast) |
|------|-----------|
| company_id |  |
| first_name |  |
| last_name |  |
| email |  |
| normalized_email |  |
| phone |  |
| normalized_phone |  |
| mobile |  |
| position |  |
| department |  |
| status |  |
| marketing_status |  |
| birthday | date |
| address |  |
| city |  |
| country |  |
| notes |  |
| social_links | array |
| preferred_contact |  |
| created_by |  |
| last_contacted_at | datetime |
| ac_id |  |
| ac_hash |  |
| ac_updated_at | datetime |
| legacy_external_id |  |

## SystemSetting

| Pole | Typ (cast) |
|------|-----------|
| key |  |
| value |  |

## ProductGroup

| Pole | Typ (cast) |
|------|-----------|
| code |  |
| name |  |
| name_alt |  |
| eshop_url |  |
| parent_id |  |

## AppLink

| Pole | Typ (cast) |
|------|-----------|
| name |  |
| url |  |
| icon_url |  |
| position | integer |
| is_active | boolean |

## User

| Pole | Typ (cast) |
|------|-----------|
| name |  |
| email |  |
| password |  |
| is_admin |  |

## ContactCustomField

| Pole | Typ (cast) |
|------|-----------|
| contact_id |  |
| key |  |
| value |  |
| type |  |
| ac_field_id |  |

## OpsActivity

| Pole | Typ (cast) |
|------|-----------|
| type |  |
| status |  |
| user_id |  |
| started_at | datetime |
| finished_at | datetime |
| duration_ms |  |
| meta | array |
| log_excerpt |  |

## ChatToolAudit

| Pole | Typ (cast) |
|------|-----------|
| user_id |  |
| conversation_id |  |
| tool |  |
| intent |  |
| payload | array |
| result_meta | array |
| duration_ms |  |

## Tag

| Pole | Typ (cast) |
|------|-----------|
| name |  |
| source |  |

## ProductPriceChange

| Pole | Typ (cast) |
|------|-----------|
| product_id |  |
| old_price_cents |  |
| new_price_cents |  |
| changed_at | datetime |

## Project

| Pole | Typ (cast) |
|------|-----------|
| name |  |
| description |  |
| status |  |
| start_date | date |
| due_date | date |
| company_id |  |
| contact_id |  |
| assigned_to |  |
| created_by |  |

## Deal

| Pole | Typ (cast) |
|------|-----------|
| opportunity_id |  |
| name |  |
| amount | decimal:2 |
| close_date | date |
| status |  |
| terms |  |
| notes |  |
| signed_by_contact_id |  |
| signed_at | datetime |
| created_by |  |

## ProductAvailabilityChange

| Pole | Typ (cast) |
|------|-----------|
| product_id |  |
| old_code |  |
| new_code |  |
| old_stock_qty |  |
| new_stock_qty |  |
| changed_at | datetime |

## ContactIdentity

| Pole | Typ (cast) |
|------|-----------|
| contact_id |  |
| source |  |
| external_id |  |
| external_hash |  |

## KnowledgeNote

| Pole | Typ (cast) |
|------|-----------|
| user_id |  |
| title |  |
| content |  |
| tags | array |
| visibility |  |

## KnowledgeDocument

| Pole | Typ (cast) |
|------|-----------|
| user_id |  |
| title |  |
| source_type |  |
| mime |  |
| size |  |
| path |  |
| status |  |
| visibility |  |
| tags | array |
| error |  |
| vectorized_at | datetime |
| embedding_provider |  |
| embedding_model |  |
| embedding_dim |  |
| vectors_count |  |
| last_index_duration_ms |  |

## SalesOrderItem

| Pole | Typ (cast) |
|------|-----------|
| sales_order_id |  |
| sku |  |
| alt_code |  |
| name |  |
| name_alt |  |
| qty | decimal:3 |
| unit_price | decimal:4 |
| unit_price_disc | decimal:4 |
| cost | decimal:4 |
| cost_disc | decimal:4 |
| discounts_card | decimal:2 |
| discounts_group | decimal:2 |
| product_group |  |
| eshop_category_url |  |
| tax_code |  |
| currency |  |


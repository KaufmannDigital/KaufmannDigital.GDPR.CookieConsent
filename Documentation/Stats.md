```sql
SELECT count(consent_group), consent_group
FROM (
    SELECT GROUP_CONCAT(c1.consentidentifier ORDER BY c1.consentidentifier) AS consent_group 
    FROM `kaufmanndigital_gdpr_cookieconsent_domain_model_consentlogentry` c1 
    GROUP BY c1.userid
    ) i
GROUP BY consent_group 
ORDER BY count(consent_group) DESC
```

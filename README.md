# Job Board API with Entity-Attribute-Value (EAV) Implementation

This project implements a Job Board API with advanced filtering capabilities, using a combination of traditional relational database models and the Entity-Attribute-Value (EAV) design pattern.

## Core Features

- Standard job attributes (title, description, salary, etc.)
- Many-to-Many relationships (languages, locations, categories)
- Dynamic attributes using EAV design pattern
- Advanced filtering API with complex query capabilities
- Support for logical operators (AND/OR) and grouping

## Entity-Attribute-Value Implementation

The EAV pattern is implemented using the following structure:

### Database Tables

- `attributes`: Stores attribute definitions (name, type, options)
- `job_attribute_values`: Stores the values for each attribute for each job

### Attribute Types

The system supports the following attribute types:

- `text`: Free-text input
- `number`: Numeric values
- `boolean`: True/false values
- `date`: Date values
- `select`: Selection from predefined options

### Models

- `Attribute`: Defines the attribute metadata
- `JobAttributeValue`: Stores the actual values
- `Job`: Core entity that can have multiple dynamic attributes

## Usage Examples

### Creating a Job with Dynamic Attributes

```php
// Create a job with standard fields
$job = Job::create([
    'title' => 'Senior PHP Developer',
    'description' => 'We are looking for an experienced PHP developer...',
    'company_name' => 'TechCorp',
    'salary_min' => 80000,
    'salary_max' => 120000,
    'is_remote' => true,
    'job_type' => JobTypeEnum::FULL_TIME,
    'status' => JobStatusEnum::PUBLISHED,
    'published_at' => now(),
]);

// Add relationships
$job->languages()->attach([1, 2]); // PHP, JavaScript
$job->locations()->attach([3]); // New York
$job->categories()->attach([1]); // Web Development

// Add dynamic attributes
$job->setAttributeValues([
    'years_experience' => 5,
    'education_level' => 'Bachelor\'s Degree',
    'seniority_level' => 'Senior',
    'application_deadline' => '2023-12-31',
    'required_skills' => 'Laravel, Vue.js, MySQL',
]);
```

### Retrieving Job with Dynamic Attributes

```php
$job = Job::with(['languages', 'locations', 'categories', 'attributeValuesRelation.attribute'])->find(1);

// Access standard fields
echo $job->title;
echo $job->salary_min;

// Access relationships
foreach ($job->languages as $language) {
    echo $language->name;
}

// Access dynamic attributes
echo $job->getAttributeValueRelation('years_experience'); // Returns 5
echo $job->getAttributeValueRelation('education_level'); // Returns "Bachelor's Degree"
```

## API Filtering Syntax

The API supports advanced filtering through the `filter` query parameter.

### Basic Field Filtering

```
/api/jobs?filter=title LIKE PHP
/api/jobs?filter=salary_min>=80000
/api/jobs?filter=job_type=full-time
/api/jobs?filter=is_remote=true
```

### Relationship Filtering

```
// Jobs requiring PHP AND JavaScript
/api/jobs?filter=languages HAS_ANY (PHP,JavaScript)

// Jobs in New York OR Remote locations
/api/jobs?filter=locations IS_ANY (New York,Remote)

// Jobs that have any categories
/api/jobs?filter=categories EXISTS
```

### EAV Attribute Filtering

```
// Jobs requiring at least 3 years of experience
/api/jobs?filter=attribute:years_experience>=3

// Jobs with a senior level position
/api/jobs?filter=attribute:seniority_level=Senior

// Jobs with a health insurance benefit
/api/jobs?filter=attribute:has_health_insurance=true
```

### Complex Filtering with Logical Operators

```
// Full-time jobs requiring PHP or JavaScript, located in New York or Remote, requiring 3+ years exp
/api/jobs?filter=(job_type=full-time AND (languages HAS_ANY (PHP,JavaScript))) AND (locations IS_ANY (New York,Remote)) AND attribute:years_experience>=3

// Entry-level or Junior positions with a deadline after 2023-10-01
/api/jobs?filter=(attribute:seniority_level=Entry Level OR attribute:seniority_level=Junior) AND attribute:application_deadline>2023-10-01
```

# 🚀 Installation and Setup

### 1️⃣ Clone the Repository
```bash
git clone https://github.com/Maahmoudd/astudio.git
cd astudio
```

### 2️⃣ Install Dependencies
```bash
composer install
composer dumpautoload
```

### 3️⃣ Configure Environment
```bash
cp .env.example .env
```

### 4️⃣ Start Docker Services
```bash
./vendor/bin/sail up -d
```

### 5️⃣ Generate Application Key
```bash
./vendor/bin/sail artisan key:generate
```

### 6️⃣ Run Migrations & Seed Database
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### 7️⃣ Run Tests (Optional)
```bash
./vendor/bin/sail artisan test
```

## Testing the API

You can test the API using the provided [Postman collection](https://documenter.getpostman.com/view/39711609/2sAYkEpJsu#fcb7b559-58ed-4ae7-9de6-0398c23aa5ef) or any HTTP client:

```
GET http://0.0.0.0/api/jobs?filter=(job_type=full-time AND (languages HAS_ANY (PHP,JavaScript)))
```

## Performance Considerations

The EAV pattern offers flexibility but can impact performance with complex queries. Some optimizations implemented:

1. Proper indexing on the `job_attribute_values` table
2. Eager loading of relationships to avoid N+1 query problems
3. Query optimization in the filter builder service
4. Type casting of attribute values at the application level

## Future Improvements

- Implement caching for frequently accessed attributes
- Add full-text search capabilities
- Implement attribute value validation based on attribute type
- Add the ability to define custom attribute validation rules

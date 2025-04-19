INSERT INTO "crispy_layouts" (
        "id",
        "name",
        "content",
        "author",
        "slug",
        "created_at",
        "updated_at"
    )
VALUES (
        0,
        'Initial Template',
        '<!DOCTYPE html>
<html lang="en">
<head>
  <title>{{ Page.name }}</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="{{ includeResource("vendor/libs/ckeditor5/ckeditor5-content.css") }}"/>
</head>
<body>

<div class="container-fluid p-5 bg-primary text-white text-center">
  <h1>{% block Header %}{% endblock %}</h1>
  <p class="ck ck-content">{% block Content %}{% endblock %}</p> 
</div>
  
<div class="container mt-5">
  <div class="row">
    <div class="col-sm-4">
      <h3>Column 1</h3>
      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit...</p>
      <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris...</p>
    </div>
    <div class="col-sm-4">
      <h3>Column 2</h3>
      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit...</p>
      <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris...</p>
    </div>
    <div class="col-sm-4">
      <h3>Column 3</h3>        
      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit...</p>
      <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris...</p>
    </div>
  </div>
</div>

</body>
</html>
',
        0,
        'initial',
        '2025-02-02 13:17:29.861628',
        NULL
    );
INSERT INTO "crispy_templates" (
        "id",
        "name",
        "directory",
        "content",
        "author",
        "layout",
        "slug",
        "created_at",
        "updated_at"
    )
VALUES (
        0,
        'Initial Template',
        '',
        '{% block Header %}
 Hello World!
{% endblock %}
{% block Content %}
 {{ Page.content }}
{% endblock %}',
        0,
        0,
        'test',
        '2025-02-02 13:19:22.749977',
        NULL
    );
INSERT INTO "crispy_pages" (
        "id",
        "name",
        "content",
        "author",
        "template",
        "properties",
        "slug",
        "computed_url",
        "created_at",
        "updated_at",
        "category"
    )
VALUES (
        0,
        'Initial Page',
        'CrispCMS has been installed!',
        0,
        0,
        1,
        '',
        'initial',
        '2025-02-02 13:22:22.304129',
        NULL,
        NULL
    );


ALTER SEQUENCE crispy_layouts_id_seq START WITH 10;
ALTER SEQUENCE crispy_templates_id_seq START WITH 10;
ALTER SEQUENCE crispy_pages_id_seq START WITH 10;
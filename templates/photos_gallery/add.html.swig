{% extends 'base.html.twig' %}

{% form_theme formPicture 'bootstrap_4_layout.html.twig' %}

{% block body %}

    <h1>Adding a picture</h1>
    {{ form_start(formPicture) }}

    {{ form_row(
        formPicture.name,
        {'attr': {'placeholder': 'Picture name'}}
    )}}
    
    {{ form_widget(
        formPicture.pictureFilename
    )}}
    <hr>
{{ form_row(formPicture._token) }}
    <button type="submit" class="btn btn-success">Upload</button

    {{ form_end(formPicture) }}

{% endblock %}
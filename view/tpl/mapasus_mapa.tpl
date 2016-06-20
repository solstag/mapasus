<!--
$title
-->

<h1>{{$title}}</h1>
<p>{{$subtitle}}</p>
<div id="filters">
</div>

<form>
  <fieldset id="layers" class="collapsibleClosed">
    <legend>Layers (Clique aqui para configurar o mapa)</legend>
    <fieldset id="layer_1" data-num="1" class="layer collapsibleOpen" data-color="#000000">
      <legend>Layer 1</legend>
      <div class="meta">Foram selecionadas<br><span class="qtd">0</span> pessoas de <span class="total">0</span></div>
      <div class="row">
        <div class="col-md-2">
          <label>A partir de:</label>
          <input class="dsel s_dsel" type="text" placeholder="yyyy-mm-dd">
        </div>
        <div class="col-md-2">
          <label>Até:</label>
          <input class="dsel f_dsel" type="text" placeholder="yyyy-mm-dd">
        </div>
      </div>
      <div class="row">
        <div class="col-md-2">
          <label>Tipo do layer:</label>
        </div>
        <div class="col-md-9">
          <div class="layer_field">
            <input type="radio" name="layer_1_type" value="heat"> <label for="heatmap">Mapa de Calor</label>
          </div>
          <div class="layer_field">
            <input type="radio" name="layer_1_type" value="dots" checked> <label for="markers">Pontos da cor:</label>
            <div class="wcolorpicker" data-num="1"></div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <label>Participante do PPSUS:</label>
          <select class="ppsus_inc">
            <option value="null" selected>Indiferente</option>
            <option value="true">Sim</option>
            <option value="false">Não</option>
          </select>
        </div>
      </div>
      <button type="button" class="updateLayer btn btn-default" aria-label="{{$filter_text}}">
        <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
      </button>
    </fieldset>
    <button type="button" class="btn btn-default" aria-label="{{$add_text}}" id="newLayer">
      <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
    </button>
  </fieldset>
</form>


<div id="map"></div>

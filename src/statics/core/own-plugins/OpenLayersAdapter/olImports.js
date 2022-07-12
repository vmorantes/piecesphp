/// <reference path="./MapManager.js" />
/// <reference path="./GeoJSONVectorLayer.js" />
/// <reference path="./WMSTileLayer.js" />
/// <reference path="./OpenLayersAdapter.js" />

let OpenLayers = ol

let OlMap = OpenLayers.Map
let OlView = OpenLayers.View
let OlOverlay = OpenLayers.Overlay

let Layer = OpenLayers.layer.Layer
let LayerVector = OpenLayers.layer.Vector
let LayerTile = OpenLayers.layer.Tile

let SourceVector = OpenLayers.source.Vector
let SourceOSM = OpenLayers.source.OSM
let SourceTileWMS = OpenLayers.source.TileWMS

let FormatGeoJSON = OpenLayers.format.GeoJSON

let Style = OpenLayers.style.Style
let StyleCircle = OpenLayers.style.Circle
let StyleFill = OpenLayers.style.Fill
let StyleStroke = OpenLayers.style.Stroke

let olProj = OpenLayers.proj

let controlDefaults = OpenLayers.control.defaults
let ControlFullScreen = OpenLayers.control.FullScreen
let ControlZoomSlider = OpenLayers.control.ZoomSlider
let ControlSearchFeature = OpenLayers.control.SearchFeature

let GeometryType = {
	POINT: 'Point',
	LINE_STRING: 'LineString',
	LINEAR_RING: 'LinearRing',
	POLYGON: 'Polygon',
	MULTI_POINT: 'MultiPoint',
	MULTI_LINE_STRING: 'MultiLineString',
	MULTI_POLYGON: 'MultiPolygon',
	GEOMETRY_COLLECTION: 'GeometryCollection',
	CIRCLE: 'Circle',
}

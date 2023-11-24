#encoding "utf-8"
#GRAMMAR_ROOT S

Furniture -> AnyWord<kwtype="мебель">;
Car -> AnyWord<kwtype="автомобиль">;
Animal -> AnyWord<kwtype="животные">;
Appliances -> AnyWord<kwtype="бытовая_техника">;
HoseThings -> AnyWord<kwtype="домашние_вещи">;
Food -> AnyWord<kwtype="продукты">;
Oil -> AnyWord<kwtype="нефтепродукт">;
Vegetables -> AnyWord<kwtype="овощи">;
Fish -> AnyWord<kwtype="рыба">;
Wood -> AnyWord<kwtype="дерево">;
Equipment -> AnyWord<kwtype="оборудование">;
Metal -> AnyWord<kwtype="металлопрокат">;
Danger -> AnyWord<kwtype="опасный">;
Garbage -> AnyWord<kwtype="мусор">;

S -> Furniture|Car|Animal|Appliances|HoseThings|Food|Vegetables|Fish|Wood|Equipment|Metal|Danger|Garbage;

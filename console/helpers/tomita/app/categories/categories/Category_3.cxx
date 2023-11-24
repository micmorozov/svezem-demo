#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 3 - Перевозка вещей

Transp -> AnyWord<kwtype="перевезти">;
What -> AnyWord<kwtype="домашние_вещи"> | AnyWord<kwtype="бытовая_техника">;

TranspThing -> What;

NotUtilize -> AnyWord<kwtype=~"утилизировать">;
NotUtilizeThing -> NotUtilize What;

Fact -> TranspThing | NotUtilizeThing;

S -> Fact interp(Category.Category_3);

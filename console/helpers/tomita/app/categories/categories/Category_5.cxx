#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 5 - Перевозка продуктов питания

Food -> AnyWord<kwtype="продукты">;
Fish -> AnyWord<kwtype="рыба">;
Vegetable -> AnyWord<kwtype="овощи">;

What -> Food | Fish | Vegetable;

Fact -> What;

S -> Fact interp(Category.Category_5);

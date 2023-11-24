#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 31 - Перевозка рыбы

What -> AnyWord<kwtype="рыба">;;

Fact -> What;

S -> Fact interp(Category.Category_31);

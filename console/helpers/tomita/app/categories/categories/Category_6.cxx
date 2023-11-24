#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 6 - Перевозка автомобилей

/*
Перевезти 2|два автомобиля
*/

//Transp -> AnyWord<kwtype="перевезти">;
Fact -> AnyWord<kwtype="автомобиль">;

//Fact -> (Transp) (AnyWord+) What;

S -> Fact interp(Category.Category_6);

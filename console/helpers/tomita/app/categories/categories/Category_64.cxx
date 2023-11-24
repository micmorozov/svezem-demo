#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 64 - Перевозка нефтепродуктов

Transp -> AnyWord<kwtype="перевезти">;
What -> AnyWord<kwtype="нефтепродукт">;

//Fact -> Transp (AnyWord+) What;
Fact -> What;

S -> Fact interp(Category.Category_64);

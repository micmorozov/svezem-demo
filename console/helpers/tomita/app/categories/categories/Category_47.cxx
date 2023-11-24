#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 47 - Перевозка мебели

Transp -> AnyWord<kwtype="перевезти">;
What -> AnyWord<kwtype="мебель">;

//Fact -> Transp (AnyWord+) What;
Fact -> What;

S -> Fact interp(Category.Category_47);

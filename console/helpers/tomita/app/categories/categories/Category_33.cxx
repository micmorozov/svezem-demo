#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 33 - Перевозка овощей

//Transp -> AnyWord<kwtype="перевезти">;
Vegetables -> AnyWord<kwtype="овощи">;
Fact -> Vegetables;

S -> Fact interp(Category.Category_33);

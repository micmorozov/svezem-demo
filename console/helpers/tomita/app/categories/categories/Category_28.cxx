#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 28 - Перевозка погрузчиков

/*
перевозти 2 погрузчика
*/
Fact -> "автопогрузчик" | "погрузчик";

S -> Fact interp(Category.Category_28);